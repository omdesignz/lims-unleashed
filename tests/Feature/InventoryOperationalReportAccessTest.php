<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionType;
use App\Models\ItemCategory;
use App\Models\Permission;
use App\Models\ReagentConsumption;
use App\Models\User;
use App\Models\VAPLab;
use App\Services\InventoryCatalogueRead;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InventoryOperationalReportAccessTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('reportGrants')]
    public function test_reports_rows_aggregates_options_and_user_payloads_are_kind_private(string $report, string $grant): void
    {
        [, , $data] = $this->fixture($grant);
        $response = $this->get(route(self::routeName($report)));
        if ($grant === 'none') {
            $response->assertForbidden();

            return;
        }
        $props = $response->assertOk()->viewData('page')['props'];
        $types = $grant === 'both' ? ['material', 'equipment'] : [$grant];
        $rows = $props[self::rowsKey($report)]['data'];
        $this->assertCount(count($types), $rows);
        foreach ($rows as $row) {
            $this->assertContains($row['item']['id'], array_map(fn (string $type): int => $data[$type]['item']->id, $types));
            $this->assertArrayNotHasKey('obs', $row['item']);
            $this->assertArrayNotHasKey('lab_id', $row['item']);
            if ($report !== 'inventory_value') {
                $this->assertSame(['id', 'name'], array_keys($row['user']));
                $this->assertArrayNotHasKey('standard_cost', $row['item']);
            }
        }
        if ($report === 'stock_movement') {
            $this->assertSame(['user_id', 'transaction_count', 'user'], array_keys($props['stats']['most_active_user']));
            $this->assertSame(['id', 'name'], array_keys($props['stats']['most_active_user']['user']));
            $this->assertSame(count($types), $props['stats']['total_transactions']);
            $this->assertSame(1.25 * count($types), $props['stats']['total_in']);
            $this->assertSame([count($types), 0, 0, 0], $props['charts']['type_mix']['series']);
            $this->assertSame([1.25 * count($types)], $props['charts']['daily_activity']['series'][0]['data']);
            $this->assertCount(2 * count($types), $props['items']);
        } elseif ($report === 'consumption') {
            $this->assertSame(count($types), (int) $props['stats']['total_uses']);
            $this->assertSame(0.125 * count($types), (float) $props['stats']['total_consumption']);
            $this->assertCount(count($types), $props['summaryByItem']);
            $this->assertCount(count($types), $props['users']);
            foreach ($props['users'] as $user) {
                $this->assertSame(['id', 'name'], array_keys($user));
            }
        } else {
            $this->assertSame(count($types), $props['stats']['stock_positions']);
            $this->assertSame(count($types), $props['stats']['unique_items']);
            $this->assertSame(12.5 * count($types), (float) $props['stats']['total_value']);
            $this->assertCount(count($types), $props['topValuableItems']);
        }
    }

    #[DataProvider('exportGrants')]
    public function test_report_csvs_use_the_same_owner_and_view_kind_scope(string $report, string $grant): void
    {
        [, , $data] = $this->fixture($grant);
        $response = $this->post(route('vap-inventory.reports.export'), ['report_type' => $report, 'format' => 'csv']);
        if ($grant === 'none') {
            $response->assertForbidden();

            return;
        }
        $response->assertOk();
        foreach (['material', 'equipment'] as $type) {
            if ($grant === 'both' || $grant === $type) {
                $this->assertStringContainsString($data[$type]['item']->name, $response->getContent());
            } else {
                $this->assertStringNotContainsString($data[$type]['item']->name, $response->getContent());
            }
            $this->assertStringNotContainsString($data[$type]['peer']->name, $response->getContent());
        }
    }

    #[DataProvider('grants')]
    public function test_dashboard_stock_consumption_and_activity_are_kind_private(string $grant): void
    {
        [, $user] = $this->fixture($grant);
        $response = $this->getJson(route('vap-inventory.reports.dashboard-stats'));
        if ($grant === 'none') {
            $response->assertForbidden();

            return;
        }
        $count = $grant === 'both' ? 2 : 1;
        $response->assertOk()->assertJsonPath('stats.total_items', 2 * $count)
            ->assertJsonPath('stats.total_stock_value', fn ($value): bool => (is_int($value) || is_float($value)) && (float) $value === 12.5 * $count)
            ->assertJsonPath('stats.low_stock_items', 2 * $count)
            ->assertJsonPath('stats.today_consumption', 0.125 * $count)
            ->assertJsonCount($count, 'recent_activity')->assertJsonCount($count, 'top_consumed')
            ->assertJsonPath('stats.pending_transfers', null)->assertJsonPath('stats.pending_orders', null);
        $user->revokePermissionTo('view_itransactions');
        $this->getJson(route('vap-inventory.reports.dashboard-stats'))->assertOk()->assertJsonCount(0, 'recent_activity');
    }

    #[DataProvider('reports')]
    public function test_filters_narrow_rows_and_all_totals_without_exposing_another_kind(string $report): void
    {
        [, , $data] = $this->fixture('material');
        foreach (['item_id' => $data['equipment']['item']->id, 'warehouse_id' => $data['equipment']['warehouse']->id, 'search' => 'not-present'] as $field => $value) {
            $props = $this->get(route(self::routeName($report), [$field => $value]))->assertOk()->viewData('page')['props'];
            $this->assertSame([], $props[self::rowsKey($report)]['data']);
            $key = match ($report) {
                'stock_movement' => 'total_transactions',
                'consumption' => 'total_uses',
                'inventory_value' => 'stock_positions',
            };
            $this->assertSame(0, (int) $props['stats'][$key]);
            if ($report === 'inventory_value') {
                $this->assertSame(0.0, (float) $props['stats']['total_value']);
            }
            $export = $this->post(route('vap-inventory.reports.export'), ['report_type' => $report, 'format' => 'csv', 'filters' => [$field => $value]])->assertOk();
            $this->assertStringNotContainsString($data['material']['item']->name, $export->getContent());
            $this->assertStringNotContainsString($data['equipment']['item']->name, $export->getContent());
        }
    }

    #[DataProvider('reports')]
    public function test_invalid_dates_scalars_sort_and_page_limits_are_rejected(string $report): void
    {
        $this->fixture('both');
        foreach (['item_id' => [1], 'per_page' => 101, 'page' => 0, 'search' => ['bad'], 'sort_by' => 'private_field', 'sort_direction' => 'bad', 'date_from' => 'not-a-date'] as $key => $value) {
            $this->getJson(route(self::routeName($report), [$key => $value]))->assertUnprocessable()->assertJsonValidationErrors($key);
        }
        $this->getJson(route(self::routeName($report), ['date_from' => '2026-10-03', 'date_to' => '2026-10-02']))->assertUnprocessable()->assertJsonValidationErrors('date_to');
        $this->get(route(self::routeName($report), ['date_to' => now()->toDateString()]))->assertOk();
        $props = $this->get(route(self::routeName($report), ['per_page' => 1]))->assertOk()->viewData('page')['props'];
        $this->assertCount(1, $props[self::rowsKey($report)]['data']);
        $this->assertSame(2, $props[self::rowsKey($report)]['total']);
    }

    #[DataProvider('reports')]
    public function test_archived_item_category_warehouse_and_author_keep_authorized_history_readable(string $report): void
    {
        [, , $data] = $this->fixture('material');
        $data['material']['item']->delete();
        $data['material']['item']->category->delete();
        $data['material']['warehouse']->delete();
        $data['material']['author']->delete();
        $props = $this->get(route(self::routeName($report), ['category_id' => $data['material']['item']->category_id]))->assertOk()->viewData('page')['props'];
        $this->assertCount(1, $props[self::rowsKey($report)]['data']);
        $this->assertSame($data['material']['item']->name, $props[self::rowsKey($report)]['data'][0]['item']['name']);
        $this->post(route('vap-inventory.reports.export'), ['report_type' => $report, 'format' => 'csv'])
            ->assertOk()->assertSee($data['material']['item']->name, false);
    }

    #[DataProvider('reports')]
    public function test_permission_revocation_and_lab_switch_are_current(string $report): void
    {
        [$lab, $user, $data] = $this->fixture('material');
        $peerId = $data['material']['peer']->lab_id;
        DB::table('lab_user')->insert(['lab_id' => $peerId, 'user_id' => $user->id]);
        $props = $this->withSession(['active_lab_id' => $peerId])->get(route(self::routeName($report), ['lab_id' => $lab->id]))->assertOk()->viewData('page')['props'];
        $this->assertCount(1, $props[self::rowsKey($report)]['data']);
        $this->assertSame($data['material']['peer']->id, $props[self::rowsKey($report)]['data'][0]['item']['id']);
        $user->revokePermissionTo('view_iitems');
        $this->get(route(self::routeName($report)))->assertForbidden();
        $user->givePermissionTo('view_iitems');
        DB::table('lab_user')->where('user_id', $user->id)->delete();
        $this->get(route(self::routeName($report)))->assertForbidden();
    }

    public function test_report_operations_have_independent_read_permissions(): void
    {
        [, $user] = $this->fixture('material');
        $user->revokePermissionTo('view_itransactions');
        $this->get(route(self::routeName('stock_movement')))->assertForbidden();
        $this->post(route('vap-inventory.reports.export'), ['report_type' => 'stock_movement', 'format' => 'csv'])->assertForbidden();
        $this->get(route(self::routeName('consumption')))->assertOk();
        $user->givePermissionTo('view_itransactions');
        $user->revokePermissionTo('view_inventory');
        $this->get(route(self::routeName('consumption')))->assertForbidden();
        $this->get(route(self::routeName('inventory_value')))->assertForbidden();
        $this->getJson(route('vap-inventory.reports.dashboard-stats'))->assertForbidden();
    }

    #[DataProvider('grants')]
    public function test_ledger_alias_cannot_bypass_kind_access(string $grant): void
    {
        [, , $data] = $this->fixture($grant);
        $response = $this->get(route('itransactions.index'));
        if ($grant === 'none') {
            $response->assertForbidden();

            return;
        }
        $props = $response->assertOk()->viewData('page')['props'];
        $this->assertCount($grant === 'both' ? 2 : 1, $props['record']['data']);
        foreach (['material', 'equipment'] as $type) {
            $response = $this->get(route('itransactions.show', $data[$type]['transaction']));
            if ($grant === 'both' || $grant === $type) {
                $response->assertOk();
            } else {
                $response->assertNotFound();
            }
        }
    }

    public function test_export_filter_validation_precedes_query_execution(): void
    {
        $this->fixture('both');
        foreach (['item_id' => [1], 'date_from' => 'bad-date', 'search' => ['bad'], 'sort_direction' => 'bad'] as $field => $value) {
            $this->postJson(route('vap-inventory.reports.export'), ['report_type' => 'stock_movement', 'format' => 'csv', 'filters' => [$field => $value]])
                ->assertUnprocessable()->assertJsonValidationErrors('filters.'.$field);
        }
    }

    public function test_ledger_search_is_literal_case_insensitive_and_keeps_retained_labels(): void
    {
        [, , $data] = $this->fixture('material');
        $item = $data['material']['item'];
        $item->update(['name' => 'Retained 10%_ reagent']);
        $data['material']['warehouse']->update(['name' => 'Retained\\store']);
        $item->delete();
        $item->category->delete();
        $data['material']['warehouse']->delete();
        $data['material']['transaction']->type->delete();
        foreach (['10%_', 'retained\\store', 'STOCK IN', '1.2500'] as $search) {
            $props = $this->get(route('itransactions.index', ['search' => $search]))->assertOk()->viewData('page')['props'];
            $this->assertCount(1, $props['record']['data']);
            $this->assertSame($data['material']['transaction']->id, $props['record']['data'][0]['id']);
        }
        $props = $this->get(route('itransactions.index', ['search' => '11%_']))->assertOk()->viewData('page')['props'];
        $this->assertSame([], $props['record']['data']);
        $props = $this->get(route('itransactions.index', ['search' => 'Operational equipment']))->assertOk()->viewData('page')['props'];
        $this->assertSame([], $props['record']['data']);
    }

    public function test_archived_movement_type_stays_in_rows_totals_exports_and_activity(): void
    {
        [, , $data] = $this->fixture('material');
        $data['material']['transaction']->type->delete();
        $data['material']['author']->delete();
        $props = $this->get(route(self::routeName('stock_movement')))->assertOk()->viewData('page')['props'];
        $this->assertSame('Stock in', $props['transactions']['data'][0]['type']['name']);
        $this->assertSame(1.25, $props['stats']['total_in']);
        $this->assertSame([1.25, 0.0, 1.25], $props['charts']['direction_breakdown']['series'][0]['data']);
        $this->getJson(route('vap-inventory.reports.dashboard-stats'))->assertOk()
            ->assertJsonPath('recent_activity.0.type', 'Stock in')
            ->assertJsonPath('recent_activity.0.user', $data['material']['author']->name);
        $this->post(route('vap-inventory.reports.export'), ['report_type' => 'stock_movement', 'format' => 'csv'])
            ->assertOk()->assertSee('Stock in', false);
    }

    public function test_purchase_receipts_are_incoming_movements_in_rows_summaries_charts_and_exports(): void
    {
        [, , $data] = $this->fixture('material');
        $type = $data['material']['transaction']->type;
        $type->update(['code' => 'RECEIPT', 'name' => 'Purchase receipt']);

        foreach ([false, true] as $archived) {
            if ($archived) {
                $type->delete();
            }
            $props = $this->get(route(self::routeName('stock_movement'), ['view' => 'summary']))
                ->assertOk()->viewData('page')['props'];
            $this->assertCount(1, $props['transactions']['data']);
            $this->assertTrue($props['transactions']['data'][0]['is_addition']);
            $this->assertFalse($props['transactions']['data'][0]['is_deduction']);
            $this->assertSame(1.25, $props['stats']['total_in']);
            $this->assertSame(0.0, $props['stats']['total_out']);
            $this->assertSame(1.25, $props['stats']['net_movement']);
            $this->assertSame(1.25, (float) $props['summary'][0]['total_in']);
            $this->assertSame([1, 0, 0, 0], $props['charts']['type_mix']['series']);
            $this->assertSame([1.25], $props['charts']['daily_activity']['series'][0]['data']);
            $this->assertSame([1.25, 0.0, 1.25], $props['charts']['direction_breakdown']['series'][0]['data']);
            $ledger = $this->get(route('itransactions.index'))->assertOk()->viewData('page')['props']['record']['data'];
            $this->assertCount(1, $ledger);
            $this->assertTrue($ledger[0]['is_addition']);
            $this->assertFalse($ledger[0]['is_deduction']);
            $this->post(route('vap-inventory.reports.export'), ['report_type' => 'stock_movement', 'format' => 'csv'])
                ->assertOk()->assertSee('Purchase receipt', false)->assertDontSee($data['material']['peer']->name, false);
        }
    }

    public function test_movement_direction_classification_preserves_known_codes_and_leaves_unknown_types_neutral(): void
    {
        foreach ([
            'stock_in' => [true, false], 'stock_adjustment_add' => [true, false],
            'consumption_reversal' => [true, false], 'RECEIPT' => [true, false],
            'stock_out' => [false, true], 'stock_adjustment_remove' => [false, true],
            'consumption' => [false, true], 'transfer' => [false, false], 'unknown' => [false, false],
        ] as $code => [$addition, $deduction]) {
            $movement = (new InventoryTransaction)->setRelation('type', new InventoryTransactionType(['code' => $code]));
            $this->assertSame($addition, $movement->is_addition, $code);
            $this->assertSame($deduction, $movement->is_deduction, $code);
        }
        $missingType = (new InventoryTransaction)->setRelation('type', null);
        $this->assertFalse($missingType->is_addition);
        $this->assertFalse($missingType->is_deduction);
    }

    #[DataProvider('datedReports')]
    public function test_daily_averages_use_the_same_filtered_inclusive_date_window(string $report): void
    {
        [, , $data] = $this->fixture('material');
        $transaction = $data['material']['transaction'];
        $transaction->update(['created_at' => '2020-01-03 12:00:00']);
        $transaction->replicate()->fill(['created_at' => '2020-01-01 12:00:00'])->save();
        $consumption = ReagentConsumption::query()->where('reagent_id', $data['material']['item']->id)->firstOrFail();
        $consumption->update(['date' => '2020-01-03']);
        $consumption->replicate()->fill(['date' => '2020-01-01'])->save();
        foreach ([
            [[], 0.67, 0.0833],
            [['date_from' => '2020-01-01', 'date_to' => '2020-01-04'], 0.5, 0.0625],
            [['date_to' => '2020-01-02'], 0.5, 0.0625],
            [['date_from' => '2020-01-02'], 0.5, 0.0625],
            [['date_from' => '2020-01-03', 'date_to' => '2020-01-03'], 1.0, 0.125],
            [['date_from' => '2021-01-01', 'date_to' => '2021-01-02'], 0.0, 0.0],
        ] as [$filters, $movementAverage, $consumptionAverage]) {
            $props = $this->get(route(self::routeName($report), $filters))->assertOk()->viewData('page')['props'];
            $key = $report === 'stock_movement' ? 'avg_daily_transactions' : 'avg_daily_consumption';
            $this->assertSame($report === 'stock_movement' ? $movementAverage : $consumptionAverage, (float) $props['stats'][$key]);
        }
    }

    /** @return array<string,array{string}> */
    public static function datedReports(): array
    {
        return ['movement' => ['stock_movement'], 'consumption' => ['consumption']];
    }

    public function test_export_scope_preparation_intersects_read_and_export_kinds(): void
    {
        [$lab, $user, $data] = $this->fixture('both');
        $user->givePermissionTo(Permission::findOrCreate('export_iitems', 'web'));
        $read = app(InventoryCatalogueRead::class);
        $this->assertSame(['material'], $read->allowedTypes($user, 'export'));
        $this->assertSame([$data['material']['transaction']->id], $read->transactions($lab->id, $user, 'export')->pluck('id')->all());
        $user->revokePermissionTo('view_iitems');
        $this->assertSame([], $read->allowedTypes($user, 'export'));
        $this->assertSame(0, $read->stock($lab->id, $user, 'export')->count());
    }

    public function test_low_stock_export_respects_the_selected_severity(): void
    {
        [, , $data] = $this->fixture('both');
        Inventory::query()->where('item_id', $data['material']['item']->id)->update(['min_stock_level' => 2]);
        Inventory::query()->where('item_id', $data['equipment']['item']->id)->update(['min_stock_level' => 1]);
        foreach (['critical' => 'material', 'low' => 'equipment'] as $severity => $type) {
            $response = $this->post(route('vap-inventory.reports.export'), [
                'report_type' => 'low_stock', 'format' => 'csv', 'filters' => ['severity' => $severity],
            ])->assertOk();
            $response->assertSee($data[$type]['item']->name, false);
            $other = $type === 'material' ? 'equipment' : 'material';
            $this->assertStringNotContainsString($data[$other]['item']->name, $response->getContent());
        }
    }

    #[DataProvider('reports')]
    public function test_database_rejects_mismatched_foreign_warehouse_and_item_links(string $report): void
    {
        [, , $data] = $this->fixture('material');
        $item = $data['material']['item'];
        $peer = $data['material']['peer'];
        $warehouse = Inventory::query()->where('item_id', $peer->id)->value('warehouse_id');
        $query = match ($report) {
            'stock_movement' => InventoryTransaction::query()->whereKey($data['material']['transaction']->id),
            'consumption' => ReagentConsumption::query()->where('reagent_id', $item->id),
            default => Inventory::query()->where('item_id', $item->id),
        };
        $model = $query->firstOrFail();
        $table = $model->getTable();
        $column = $report === 'consumption' ? 'reagent_id' : 'item_id';
        foreach ([['warehouse_id' => $warehouse], [$column => $peer->id]] as $changes) {
            try {
                DB::transaction(fn () => DB::table($table)->where('id', $model->id)->update($changes));
                $this->fail('A cross-laboratory position link must be rejected.');
            } catch (QueryException $exception) {
                $this->assertSame('23503', $exception->getCode());
            }
        }
        $props = $this->get(route(self::routeName($report)))->assertOk()->viewData('page')['props'];
        $this->assertCount(1, $props[self::rowsKey($report)]['data']);
        $this->assertSame($item->id, $props[self::rowsKey($report)]['data'][0]['item']['id']);
    }

    public function test_csv_neutralizes_text_formulas_without_changing_signed_quantity(): void
    {
        [, , $data] = $this->fixture('material');
        $data['material']['item']->update(['name' => "\t=1+1"]);
        $data['material']['transaction']->update(['notes' => '@SUM(1)', 'qty' => '-1.2500']);
        $response = $this->post(route('vap-inventory.reports.export'), ['report_type' => 'stock_movement', 'format' => 'csv'])->assertOk();
        $lines = explode("\n", trim($response->getContent()));
        $row = str_getcsv($lines[1], ',', '"', '');
        $this->assertSame("'\t=1+1", $row[1]);
        $this->assertSame('-1.2500', $row[5]);
        $this->assertSame("'@SUM(1)", $row[9]);
    }

    /** @return array<string,array{string}> */
    public static function grants(): array
    {
        return ['material' => ['material'], 'equipment' => ['equipment'], 'both' => ['both'], 'none' => ['none']];
    }

    /** @return array<string,array{string}> */
    public static function reports(): array
    {
        return ['movement' => ['stock_movement'], 'consumption' => ['consumption'], 'value' => ['inventory_value']];
    }

    /** @return array<string,array{string,string}> */
    public static function reportGrants(): array
    {
        $cases = [];
        foreach (self::reports() as [$report]) {
            foreach (self::grants() as [$grant]) {
                $cases[$report.'-'.$grant] = [$report, $grant];
            }
        }

        return $cases;
    }

    /** @return array<string,array{string,string}> */
    public static function exportGrants(): array
    {
        return [...self::reportGrants(), 'low-stock-material' => ['low_stock', 'material'], 'low-stock-equipment' => ['low_stock', 'equipment'], 'low-stock-both' => ['low_stock', 'both'], 'low-stock-none' => ['low_stock', 'none']];
    }

    private static function routeName(string $report): string
    {
        return 'vap-inventory.reports.'.str_replace('_', '-', $report);
    }

    private static function rowsKey(string $report): string
    {
        return match ($report) {
            'stock_movement' => 'transactions',
            'consumption' => 'consumptions',
            'inventory_value' => 'inventory',
        };
    }

    /** @return array{VAPLab,User,array<string,array{item:InventoryItem,peer:InventoryItem,warehouse:InventoryItemWarehouse,author:User,transaction:InventoryTransaction}>} */
    private function fixture(string $grant): array
    {
        $lab = VAPLab::factory()->create();
        $peerLab = VAPLab::factory()->create();
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        foreach (['view_inventory', 'view_itransactions', ...($grant === 'both' ? ['view_iitems', 'view_iequipments'] : match ($grant) {
            'material' => ['view_iitems'], 'equipment' => ['view_iequipments'], default => []
        })] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $type = InventoryTransactionType::query()->firstOrCreate(['code' => 'stock_in'], ['name' => 'Stock in']);
        $data = [];
        foreach (['material', 'equipment'] as $kind) {
            $author = User::factory()->create(['name' => 'Operator '.$kind]);
            $category = ItemCategory::query()->create(['name' => 'Neutral report '.$kind.' '.fake()->uuid(), 'inventory_type' => $kind]);
            $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'category_id' => $category->id, 'name' => 'Operational '.$kind, 'standard_cost' => 10, 'obs' => 'Private item notes']);
            $warehouse = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Operational warehouse '.$kind]);
            $stock = Inventory::query()->create(['item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'qty_available' => '1.2500', 'reorder_point' => 3]);
            $transaction = InventoryTransaction::query()->create(['inventory_id' => $stock->id, 'item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'user_id' => $author->id, 'type_id' => $type->id, 'qty' => '1.2500']);
            ReagentConsumption::query()->create(['reagent_id' => $item->id, 'reagent_name' => $item->name, 'quantity_used' => '0.1250', 'date' => now()->toDateString(), 'used_at' => now(), 'warehouse_id' => $warehouse->id, 'user_id' => $author->id, 'used_by' => $author->name]);
            $zero = InventoryItem::query()->create(['lab_id' => $lab->id, 'category_id' => $category->id, 'name' => 'Zero '.$kind]);
            Inventory::query()->create(['item_id' => $zero->id, 'warehouse_id' => $warehouse->id, 'qty_available' => 0]);
            $peer = InventoryItem::query()->create(['lab_id' => $peerLab->id, 'category_id' => $category->id, 'name' => 'Peer operational '.$kind, 'standard_cost' => 900]);
            $peerWarehouse = InventoryItemWarehouse::query()->create(['lab_id' => $peerLab->id, 'name' => 'Peer operational warehouse '.$kind]);
            $peerStock = Inventory::query()->create(['item_id' => $peer->id, 'warehouse_id' => $peerWarehouse->id, 'qty_available' => 9]);
            InventoryTransaction::query()->create(['inventory_id' => $peerStock->id, 'item_id' => $peer->id, 'warehouse_id' => $peerWarehouse->id, 'user_id' => $author->id, 'type_id' => $type->id, 'qty' => 9]);
            ReagentConsumption::query()->create(['reagent_id' => $peer->id, 'reagent_name' => $peer->name, 'quantity_used' => 9, 'date' => now()->toDateString(), 'used_at' => now(), 'warehouse_id' => $peerWarehouse->id, 'user_id' => $author->id]);
            $data[$kind] = compact('item', 'peer', 'warehouse', 'author', 'transaction');
        }
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        return [$lab, $user, $data];
    }
}
