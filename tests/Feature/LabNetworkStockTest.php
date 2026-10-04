<?php

namespace Tests\Feature;

use App\Models\LabNetwork;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Exceptions\StreamedResponseException;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LabNetworkStockTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('lims_unleashed_test', DB::connection()->getDatabaseName());
        $this->travelTo(now()->setDate(2026, 10, 3)->setTime(10, 0));
    }

    /** @return array{LabNetwork, VAPLab, VAPLab, User} */
    private function network(): array
    {
        $network = LabNetwork::factory()->create();
        $main = VAPLab::factory()->create(['network_id' => $network->id]);
        $peer = VAPLab::factory()->create(['network_id' => $network->id]);
        $network->update(['main_lab_id' => $main->id]);
        $user = User::factory()->create(['is_active' => true]);
        DB::table('lab_user')->insert(['user_id' => $user->id, 'lab_id' => $main->id, 'can_view_network' => true]);
        $this->actingAs($user)->withSession(['active_lab_id' => $main->id]);

        return [$network, $main, $peer, $user];
    }

    /** @return array{id:int, warehouse:int, item:int} */
    private function stock(VAPLab $lab, string $name = 'Solvent', string $quantity = '10.0000', string $status = 'AVAILABLE', ?string $expiry = null): array
    {
        $warehouse = DB::table('i_warehouses')->insertGetId(['name' => 'Warehouse '.$name, 'lab_id' => $lab->id]);
        $unit = DB::table('i_units')->where('code', 'L')->value('id')
            ?? DB::table('i_units')->insertGetId(['description' => 'Litres', 'code' => 'L']);
        $item = DB::table('i_items')->insertGetId(['lab_id' => $lab->id, 'name' => $name, 'unit_id' => $unit,
            'reagent_expiry_date' => $expiry, 'standard_cost' => '999.0000', 'obs' => 'PRIVATE ITEM NOTES']);
        $id = DB::table('inventory')->insertGetId(['lab_id' => $lab->id, 'warehouse_id' => $warehouse, 'item_id' => $item,
            'qty_available' => $quantity, 'status' => $status, 'updated_at' => now()->subHour(), 'reorder_point' => '2.0000']);

        return compact('id', 'warehouse', 'item');
    }

    private function batch(VAPLab $lab, int $position, string $lot, string $quantity, ?string $expiry): void
    {
        DB::table('i_inventory_batches')->insert(['lab_id' => $lab->id, 'inventory_id' => $position, 'batch_number' => $lot,
            'qty_received' => $quantity, 'qty_remaining' => $quantity, 'expiry_date' => $expiry, 'created_at' => now()->subMonth()]);
    }

    private function page(LabNetwork $network, array $filters = []): array
    {
        return $this->get(route('lab-network.index', [$network, ...$filters]))->assertOk()->inertiaProps();
    }

    public function test_lots_partition_physical_stock_and_expiry_blocks_only_affected_quantities(): void
    {
        [$network, , $peer] = $this->network();
        $stock = $this->stock($peer, quantity: '10.1250');
        $this->batch($peer, $stock['id'], 'EXPIRED', '3.1250', '2026-10-02');
        $this->batch($peer, $stock['id'], 'TODAY', '4.0000', '2026-10-03');
        $page = $this->page($network);
        $rows = collect($page['stock']['data'])->keyBy('lot');
        $this->assertCount(3, $rows);
        $this->assertSame('3.1250', $rows['EXPIRED']['blocked_quantity']);
        $this->assertSame('0.0000', $rows['EXPIRED']['available_quantity']);
        $this->assertSame('4.0000', $rows['TODAY']['available_quantity']);
        $this->assertSame('3.0000', $rows['']['available_quantity']);
        $this->assertSame(10.125, $rows->sum(fn (array $row): float => (float) $row['physical_quantity']));
        $this->assertSame(1, collect($page['labs'])->firstWhere('id', $peer->id)['positions']);
        $this->assertSame(now()->subHour()->toIso8601String(), $rows['TODAY']['updated_at']);
        $this->assertNotEmpty($page['asOf']);
    }

    public function test_search_filters_apply_to_the_matching_lot_and_do_not_repeat_parent_balance(): void
    {
        [$network, , $peer] = $this->network();
        $stock = $this->stock($peer);
        $this->batch($peer, $stock['id'], 'LOT-A', '3.0001', '2026-10-02');
        $this->batch($peer, $stock['id'], 'LOT-B', '4.0000', '2026-10-10');
        $this->stock($peer, 'Other');
        $filters = ['lab_id' => $peer->id, 'warehouse_id' => $stock['warehouse'], 'lot' => 'lot-b', 'expiry_from' => '2026-10-03', 'expiry_to' => '2026-10-10', 'available' => 1];
        $page = $this->page($network, $filters);
        $this->assertSame(1, $page['stock']['total']);
        $this->assertSame('4.0000', $page['stock']['data'][0]['physical_quantity']);
        $this->assertSame(0, $this->page($network, ['lot' => 'LOT-A', 'available' => 1])['stock']['total']);
        $this->assertSame(1, $this->page($network, ['search' => 'lot-b'])['stock']['total']);
    }

    public function test_reserved_blocked_zero_and_expired_positions_are_not_advertised_as_available(): void
    {
        [$network, $main] = $this->network();
        $this->stock($main, 'Committed', '2.5000', 'COMMITED');
        $this->stock($main, 'Held', '3.0000', 'ON_HOLD');
        $this->stock($main, 'Expired', '4.0000', 'AVAILABLE', '2026-10-02');
        $this->stock($main, 'Empty', '0.0000');
        $rows = collect($this->page($network)['stock']['data'])->keyBy('name');
        $this->assertSame('2.5000', $rows['Committed']['reserved_quantity']);
        $this->assertSame('0.0000', $rows['Committed']['blocked_quantity']);
        $this->assertSame('3.0000', $rows['Held']['blocked_quantity']);
        $this->assertSame('expired', $rows['Expired']['availability_state']);
        $this->assertCount(4, $rows);
        $this->assertSame(0, $this->page($network, ['available' => 1])['stock']['total']);
        $this->assertStringContainsString('Sem saldo', $this->get(route('lab-network.export', $network))->assertOk()->streamedContent());
    }

    public function test_outgoing_transfers_are_shown_once_without_deducting_them_twice(): void
    {
        [$network, $main] = $this->network();
        $stock = $this->stock($main, quantity: '6.7500');
        $this->batch($main, $stock['id'], 'BATCH', '2.0000', null);
        $destination = DB::table('i_warehouses')->insertGetId(['name' => 'Destination', 'lab_id' => $main->id]);
        DB::table('i_transfers')->insert(['lab_id' => $main->id, 'item_id' => $stock['item'], 'source_id' => $stock['warehouse'],
            'destination_id' => $destination, 'qty' => '3.2500', 'sent_date' => '2026-10-02', 'obs' => 'PRIVATE TRANSFER NOTES']);
        $rows = collect($this->page($network)['stock']['data']);
        $this->assertSame(6.75, $rows->sum(fn (array $row): float => (float) $row['available_quantity']));
        $this->assertSame(3.25, $rows->sum(fn (array $row): float => (float) $row['outgoing_quantity']));
        DB::table('i_transfers')->where('source_id', $stock['warehouse'])->update(['received_date' => '2026-10-03']);
        $this->assertSame(0.0, collect($this->page($network)['stock']['data'])->sum(fn (array $row): float => (float) $row['outgoing_quantity']));
    }

    public function test_inconsistent_batch_balances_fail_closed_without_inflating_physical_stock(): void
    {
        [$network, $main] = $this->network();
        $stock = $this->stock($main, quantity: '2.0000');
        $this->batch($main, $stock['id'], 'INVALID', '3.0000', null);
        $rows = $this->page($network)['stock']['data'];
        $this->assertCount(1, $rows);
        $this->assertSame('2.0000', $rows[0]['physical_quantity']);
        $this->assertSame('0.0000', $rows[0]['available_quantity']);
        $this->assertSame('inconsistent', $rows[0]['availability_state']);
    }

    public function test_pagination_counts_and_exports_are_scoped_before_counting(): void
    {
        [$network, , $peer] = $this->network();
        for ($i = 0; $i < 27; $i++) {
            $this->stock($peer, sprintf('Shared %02d', $i));
        }
        [$other, $otherMain] = $this->network();
        $this->stock($otherMain, 'PRIVATE OTHER NETWORK');
        $viewer = User::factory()->create(['is_active' => true]);
        DB::table('lab_user')->insert(['user_id' => $viewer->id, 'lab_id' => $network->main_lab_id, 'can_view_network' => true]);
        $this->actingAs($viewer);
        $page = $this->page($network, ['page' => 2]);
        $this->assertSame(27, $page['stock']['total']);
        $this->assertCount(2, $page['stock']['data']);
        $this->assertSame('Shared 25', $page['stock']['data'][0]['name']);
        $export = $this->get(route('lab-network.export', [$network, 'page' => 2]))->assertOk();
        $csv = $export->streamedContent();
        $this->assertSame(28, count(array_filter(explode("\n", trim($csv)))));
        $this->assertStringNotContainsString('PRIVATE', $csv);
        $this->get(route('lab-network.index', $other))->assertForbidden();
        $this->get(route('lab-network.export', $other))->assertForbidden();
    }

    public function test_foreign_and_mismatched_filters_are_rejected_and_invalid_values_do_not_reach_sql(): void
    {
        [$network, $main, $peer] = $this->network();
        $peerStock = $this->stock($peer);
        $foreign = VAPLab::factory()->create();
        $foreignStock = $this->stock($foreign);
        foreach (['lab-network.index', 'lab-network.export'] as $route) {
            $this->getJson(route($route, [$network, 'lab_id' => $foreign->id]))->assertUnprocessable()->assertJsonValidationErrors('lab_id');
            $this->getJson(route($route, [$network, 'warehouse_id' => $foreignStock['warehouse']]))->assertUnprocessable()->assertJsonValidationErrors('warehouse_id');
            $this->getJson(route($route, [$network, 'lab_id' => $main->id, 'warehouse_id' => $peerStock['warehouse']]))->assertUnprocessable()->assertJsonValidationErrors('warehouse_id');
            $this->getJson(route($route, [$network, 'lab_id' => 'invalid', 'per_page' => 100000]))->assertUnprocessable()->assertJsonValidationErrors(['lab_id', 'per_page']);
            $this->getJson(route($route, [$network, 'expiry_from' => '2026-10-10', 'expiry_to' => '2026-10-01']))->assertUnprocessable()->assertJsonValidationErrors('expiry_to');
        }
    }

    public function test_revocation_is_fresh_for_page_export_and_partial_reload(): void
    {
        [$network, $main, $peer, $user] = $this->network();
        $this->stock($main, 'Own');
        $this->stock($peer, 'Peer');
        $this->assertSame(2, $this->page($network)['stock']['total']);
        DB::table('lab_user')->where('user_id', $user->id)->update(['can_view_network' => false]);
        $this->get(route('lab-network.index', $network), ['X-Inertia' => 'true', 'X-Inertia-Version' => Inertia::getVersion(), 'X-Inertia-Partial-Component' => 'LabNetwork/Index', 'X-Inertia-Partial-Data' => 'stock'])
            ->assertOk()->assertJsonPath('props.stock.total', 1);
        $csv = $this->get(route('lab-network.export', $network))->assertOk()->streamedContent();
        $this->assertStringNotContainsString('Peer', $csv);
        DB::table('lab_user')->where('user_id', $user->id)->delete();
        $this->get(route('lab-network.index', $network))->assertForbidden();
        $this->get(route('lab-network.export', $network))->assertForbidden();
    }

    public function test_lab_move_and_switch_do_not_leak_network_rows_or_grant_mutation(): void
    {
        [$network, $main, $peer, $user] = $this->network();
        $this->stock($peer, 'Moved material');
        $other = LabNetwork::factory()->create();
        $peer->update(['network_id' => $other->id]);
        $this->assertSame(0, $this->page($network)['stock']['total']);
        $this->post(route('lab-context.switch', $peer))->assertForbidden();
        DB::table('lab_user')->insert(['user_id' => $user->id, 'lab_id' => $peer->id]);
        $this->post(route('lab-context.switch', $peer))->assertRedirect();
        $this->assertSame(1, $this->page($other)['stock']['total']);
        $this->assertSame(0, $this->page($network)['stock']['total']);
        DB::table('lab_user')->where('user_id', $user->id)->where('lab_id', $main->id)->delete();
        $this->get(route('lab-network.index', $network))->assertForbidden();
    }

    public function test_indicator_bands_are_filter_independent_and_payload_fields_are_allowlisted(): void
    {
        [$network, $main, $peer] = $this->network();
        $this->stock($peer, 'Shared');
        for ($i = 0; $i < 4; $i++) {
            DB::table('sample_entries')->insert(['name' => 'PRIVATE SAMPLE '.$i, 'lab_id' => $peer->id, 'status' => 'EN_PROGRESO']);
        }
        $page = $this->page($network);
        $this->assertSame('1–4', collect($page['labs'])->firstWhere('id', $peer->id)['active_samples']);
        $this->assertSame('0', collect($page['labs'])->firstWhere('id', $main->id)['active_samples']);
        $this->assertSame($page['labs'], $this->page($network, ['search' => 'missing'])['labs']);
        $this->assertSame(['id', 'name', 'code', 'lab_name', 'warehouse_name', 'lot', 'expiry_date', 'unit', 'physical_quantity',
            'available_quantity', 'reserved_quantity', 'blocked_quantity', 'outgoing_quantity', 'availability_state', 'updated_at'], array_keys($page['stock']['data'][0]));
        $this->assertStringNotContainsString('PRIVATE', json_encode([$page['stock'], $page['labs']]));
        $this->assertStringNotContainsString('999.0000', json_encode($page['stock']));
        foreach ([5 => '5–9', 10 => '10–19', 20 => '20–49', 50 => '50+'] as $count => $band) {
            $existing = DB::table('sample_entries')->where('lab_id', $peer->id)->count();
            for ($i = $existing; $i < $count; $i++) {
                DB::table('sample_entries')->insert(['name' => 'PRIVATE SAMPLE '.$i, 'lab_id' => $peer->id, 'status' => 'EN_PAUSA']);
            }
            $this->assertSame($band, collect($this->page($network)['labs'])->firstWhere('id', $peer->id)['active_samples']);
        }
    }

    public function test_export_escapes_formulas_and_has_private_no_store_headers(): void
    {
        [$network, $main] = $this->network();
        $this->stock($main, '=HYPERLINK("unsafe")');
        $response = $this->get(route('lab-network.index', $network))->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response = $this->get(route('lab-network.export', $network))->assertOk();
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString('PRIVATE ITEM NOTES', $csv);
    }

    public function test_literal_wildcards_archives_and_null_freshness_are_handled(): void
    {
        [$network, $main] = $this->network();
        $stock = $this->stock($main, '100%_pure');
        DB::table('inventory')->where('id', $stock['id'])->update(['updated_at' => null]);
        DB::table('i_items')->where('id', $stock['item'])->update(['unit_id' => null]);
        $archived = $this->stock($main, 'Other');
        DB::table('i_warehouses')->where('id', $archived['warehouse'])->update(['deleted_at' => now()]);
        $page = $this->page($network, ['search' => '%_']);
        $this->assertSame(1, $page['stock']['total']);
        $this->assertNull($page['stock']['data'][0]['unit']);
        $this->assertNull($page['stock']['data'][0]['updated_at']);
        $this->assertSame(1, $this->page($network)['stock']['total']);
    }

    public function test_zero_search_and_expiry_upper_bound_work_without_a_lower_bound(): void
    {
        [$network, $main] = $this->network();
        $stock = $this->stock($main, 'Material zero');
        $this->batch($main, $stock['id'], '0', '1.0000', '2026-10-05');
        $this->batch($main, $stock['id'], 'ABC', '2.0000', '2026-10-08');
        $this->assertSame(1, $this->page($network, ['search' => '0'])['stock']['total']);
        $this->assertSame(1, $this->page($network, ['lot' => '0'])['stock']['total']);
        $this->assertSame(1, $this->page($network, ['expiry_to' => '2026-10-05'])['stock']['total']);
    }

    public function test_an_export_rechecks_membership_when_streaming_starts(): void
    {
        [$network, , $peer, $user] = $this->network();
        $this->stock($peer, 'REVOKED DATA');
        $response = $this->get(route('lab-network.export', $network))->assertOk();
        DB::table('lab_user')->where('user_id', $user->id)->delete();
        ob_start();
        try {
            ($response->baseResponse->getCallback())();
            $this->fail('Revoked membership must stop the export.');
        } catch (StreamedResponseException $exception) {
            $this->assertInstanceOf(HttpException::class, $exception->getInnerException());
            $this->assertSame(403, $exception->getInnerException()->getStatusCode());
        } finally {
            $content = ob_get_clean();
        }
        $this->assertSame('', $content);
    }

    public function test_reads_and_exports_do_not_write_inventory_or_expose_private_details(): void
    {
        [$network, , $peer] = $this->network();
        $stock = $this->stock($peer);
        $this->batch($peer, $stock['id'], 'LOT', '2.0000', null);
        $tables = ['inventory', 'i_items', 'i_transfers', 'i_inventory_batches', 'itransactions'];
        $snapshot = fn (): array => collect($tables)->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->orderBy('id')->get()->toJson()])->all();
        $before = $snapshot();
        $this->page($network);
        $this->get(route('lab-network.export', [$network, 'lot' => 'LOT']))->assertOk()->streamedContent();
        $this->assertSame($before, $snapshot());
    }

    public function test_main_lab_reassignment_and_user_deactivation_revoke_overview(): void
    {
        [$network, $main, $peer, $user] = $this->network();
        $this->stock($main, 'Own');
        $this->stock($peer, 'Peer');
        $network->update(['main_lab_id' => $peer->id]);
        $this->assertSame(1, $this->page($network)['stock']['total']);
        $this->assertStringNotContainsString('Peer', $this->get(route('lab-network.export', $network))->assertOk()->streamedContent());
        $user->update(['is_active' => false]);
        $this->get(route('lab-network.export', $network))->assertUnauthorized();
    }
}
