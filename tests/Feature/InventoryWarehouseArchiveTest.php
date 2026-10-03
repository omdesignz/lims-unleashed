<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\ISOActivityLog;
use App\Models\ItemCategory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Support\ExportHubQuery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InventoryWarehouseArchiveTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('transitions')]
    public function test_later_lifecycle_veto_rolls_back_the_entire_batch(bool $archived): void
    {
        [$lab, $user, $first, $second] = $this->fixture(! $archived);
        $before = $this->snapshot();
        $event = $archived ? 'deleting' : 'restoring';
        InventoryItemWarehouse::{$event}(fn (InventoryItemWarehouse $warehouse): ?bool => $warehouse->id === $second->id ? false : null);
        try {
            $response = $this->actingAs($user)->{$archived ? 'delete' : 'patch'}(route($archived ? 'iwarehouses.destroy' : 'iwarehouses.restore'),
                ['recordIds' => [$first->id, $second->id]]);
            $this->assertSame($before, $this->snapshot());
            $response->assertStatus(409);
        } finally {
            InventoryItemWarehouse::flushEventListeners();
            InventoryItemWarehouse::clearBootedModels();
        }
    }

    public static function transitions(): array
    {
        return ['archive' => [true], 'restore' => [false]];
    }

    public function test_archive_restore_and_replay_preserve_identity_and_write_one_audit_per_transition(): void
    {
        [$lab, $user, $first, $second] = $this->fixture();
        $before = $first->fresh()->getAttributes();
        $this->actingAs($user)->delete(route('iwarehouses.destroy'), ['recordIds' => [$second->id, $first->id]])->assertRedirect();
        $this->assertSoftDeleted($first);
        $this->assertSoftDeleted($second);
        $this->assertSame(2, ISOActivityLog::withoutGlobalScopes()->where('log_name', 'inventory_warehouse')->count());
        $snapshot = $this->snapshot();
        $this->delete(route('iwarehouses.destroy'), ['recordIds' => [$first->id, $second->id]])->assertRedirect();
        $this->assertSame($snapshot, $this->snapshot());
        $this->patch(route('iwarehouses.restore'), ['recordIds' => [$first->id, $second->id]])->assertRedirect();
        $this->assertSame($before, $first->fresh()->getAttributes());
        $this->assertSame(4, ISOActivityLog::withoutGlobalScopes()->where('log_name', 'inventory_warehouse')->count());
        $snapshot = $this->snapshot();
        $this->patch(route('iwarehouses.restore'), ['recordIds' => [$second->id, $first->id]])->assertRedirect();
        $this->assertSame($snapshot, $this->snapshot());
    }

    public function test_nested_route_uses_its_own_id_and_does_not_require_a_bulk_payload(): void
    {
        [$lab, $user, $first, $second] = $this->fixture();
        $this->actingAs($user)->delete(route('vap-inventory.master.warehouses.destroy', $first), ['recordIds' => [$second->id]])->assertRedirect();
        $this->assertSoftDeleted($first);
        $this->assertNull($second->fresh()->deleted_at);
        $this->patch(route('vap-inventory.master.warehouses.restore', $first))->assertRedirect();
        $this->assertNull($first->fresh()->deleted_at);
    }

    public function test_get_mutations_are_removed_and_invalid_batches_are_bounded(): void
    {
        [$lab, $user, $first] = $this->fixture();
        $this->actingAs($user);
        $before = $this->snapshot();
        foreach (['iwarehouses.destroy', 'iwarehouses.restore'] as $name) {
            $this->get(route($name, ['recordIds' => [$first->id]]))->assertStatus(405);
        }
        foreach ([[], [$first->id, $first->id], [0], [-1], ['bad'], array_fill(0, 101, $first->id), ['key' => $first->id]] as $ids) {
            $this->deleteJson(route('iwarehouses.destroy'), ['recordIds' => $ids])->assertUnprocessable();
        }
        $this->assertSame($before, $this->snapshot());
    }

    #[DataProvider('transitions')]
    public function test_mixed_lab_or_missing_ids_do_not_change_any_root(bool $archived): void
    {
        [$lab, $user, $first] = $this->fixture(! $archived);
        $peer = InventoryItemWarehouse::query()->create(['lab_id' => VAPLab::factory()->create()->id, 'name' => 'Peer']);
        $before = $this->snapshot();
        $this->actingAs($user);
        foreach ([$peer->id, 2147483647] as $id) {
            $this->{$archived ? 'delete' : 'patch'}(route($archived ? 'iwarehouses.destroy' : 'iwarehouses.restore'),
                ['recordIds' => [$first->id, $id]])->assertNotFound();
        }
        $this->assertSame($before, $this->snapshot());
    }

    #[DataProvider('auditFaults')]
    public function test_audit_and_late_authority_faults_rollback_roots_and_evidence(bool $archived, string $fault): void
    {
        [$lab, $user, $first, $second] = $this->fixture(! $archived);
        $oldAudit = activity('inventory_warehouse')->causedBy($user)->performedOn($first)->log('Retained evidence');
        $before = $this->snapshot();
        ISOActivityLog::creating(function (ISOActivityLog $audit) use ($fault, $oldAudit, $first, $user, $lab): ?bool {
            if ($audit->log_name !== 'inventory_warehouse') {
                return null;
            }
            match ($fault) {
                'new-audit' => $audit->description = 'Changed evidence',
                'retained-history' => DB::table('activity_log')->where('id', $oldAudit->id)->update(['subject_id' => 2147483647]),
                'root' => DB::table('i_warehouses')->where('id', $first->id)->update(['name' => 'Changed warehouse']),
                'laboratory' => DB::table($lab->getTable())->where('id', $lab->id)->update(['name' => 'Changed laboratory']),
                'actor' => DB::table('users')->where('id', $user->id)->update(['name' => 'Changed actor']),
                'pivot' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->update(['can_manage_branding' => true]),
                'membership' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->delete(),
                'verification' => DB::table('users')->where('id', $user->id)->update(['email_verified_at' => null]),
                'veto' => null,
            };

            return $fault === 'veto' ? false : null;
        });
        try {
            $response = $this->actingAs($user)->{$archived ? 'delete' : 'patch'}(route($archived ? 'iwarehouses.destroy' : 'iwarehouses.restore'),
                ['recordIds' => [$first->id, $second->id]]);
            $response->assertStatus(in_array($fault, ['membership', 'verification'], true) ? 403 : 409);
            $this->assertSame($before, $this->snapshot());
        } finally {
            ISOActivityLog::flushEventListeners();
            ISOActivityLog::clearBootedModels();
        }
    }

    public static function auditFaults(): array
    {
        $cases = [];
        foreach ([true, false] as $archived) {
            foreach (['new-audit', 'retained-history', 'root', 'laboratory', 'actor', 'pivot', 'membership', 'verification', 'veto'] as $fault) {
                $cases[($archived ? 'archive-' : 'restore-').$fault] = [$archived, $fault];
            }
        }

        return $cases;
    }

    public function test_warehouse_audits_require_the_owning_lab_and_warehouse_view_permission(): void
    {
        [$lab, $user, $first] = $this->fixture();
        $peer = VAPLab::factory()->create();
        $peerUser = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $peerUser->assignRole(Role::findOrCreate('admin', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $peer->id, 'user_id' => $peerUser->id, 'can_view_network' => true]);
        $peerWarehouse = InventoryItemWarehouse::query()->create(['lab_id' => $peer->id, 'name' => 'Private peer warehouse']);
        $local = activity('inventory_warehouse')->causedBy($user)->performedOn($first)->withProperties(['lab_id' => $lab->id])->log('Local evidence');
        $first->delete();
        $foreign = activity('inventory_warehouse')->causedBy($peerUser)->performedOn($peerWarehouse)->withProperties(['lab_id' => $peer->id])->log('Peer evidence');
        $limited = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $limited->givePermissionTo(Permission::findOrCreate('view_activity_log', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $limited->id]);
        foreach ([[$user, $lab, [$local->id]], [$peerUser, $peer, [$foreign->id]], [$limited, $lab, []]] as [$viewer, $activeLab, $visible]) {
            $this->actingAs($viewer)->withSession(['active_lab_id' => $activeLab->id]);
            $response = $this->get(route('systemactivity.index'))->assertOk();
            $ids = collect(data_get($response->viewData('page'), 'props.record.data'))->pluck('id')->intersect([$local->id, $foreign->id])->values()->all();
            $this->assertSame($visible, $ids);
            foreach ([$local->id, $foreign->id] as $id) {
                $this->getJson(route('systemactivity.show', $id))->assertStatus(in_array($id, $visible, true) ? 200 : 404);
            }
            request()->attributes->set('proposal_laboratory_id', $activeLab->id);
            request()->setUserResolver(fn () => $viewer);
            $this->assertSame($visible, ISOActivityLog::query()->whereIn('id', [$local->id, $foreign->id])->pluck('id')->all());
            $this->assertSame($visible, app(ExportHubQuery::class)->forDataset('activity_log', [])->whereIn('activity_log.id', [$local->id, $foreign->id])->pluck('activity_log.id')->all());
        }
        request()->attributes->remove('proposal_laboratory_id');
        $this->assertSame(0, ISOActivityLog::query()->whereIn('id', [$local->id, $foreign->id])->count());
    }

    #[DataProvider('referenceFaults')]
    public function test_restore_preserves_all_retained_stock_references(string $fault): void
    {
        [$lab, $user, $first, $second] = $this->fixture(true);
        $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Retained material',
            'category_id' => ItemCategory::query()->create(['name' => 'Category'])->id]);
        $stock = Inventory::query()->create(['lab_id' => $lab->id, 'item_id' => $item->id, 'warehouse_id' => $first->id,
            'qty_available' => '0.1250', 'min_stock_level' => '0.0000', 'reorder_point' => '0.0000', 'status' => 'AVAILABLE']);
        $before = $this->snapshot();
        if ($fault !== 'none') {
            ISOActivityLog::creating(function (ISOActivityLog $audit) use ($fault, $stock, $second): void {
                if ($audit->log_name !== 'inventory_warehouse') {
                    return;
                }
                match ($fault) {
                    'balance' => DB::table($stock->getTable())->where('id', $stock->id)->update(['qty_available' => '9.9999']),
                    'moved' => DB::table($stock->getTable())->where('id', $stock->id)->update(['warehouse_id' => $second->id]),
                    'deleted' => DB::table($stock->getTable())->where('id', $stock->id)->delete(),
                };
            });
        }
        try {
            $response = $this->actingAs($user)->patch(route('iwarehouses.restore'), ['recordIds' => [$first->id]]);
            if ($fault === 'none') {
                $response->assertRedirect();
                $this->assertSame($before['inventory'], $this->snapshot()['inventory']);
                $this->assertNull($first->fresh()->deleted_at);
            } else {
                $response->assertStatus(409);
                $this->assertSame($before, $this->snapshot());
            }
        } finally {
            ISOActivityLog::flushEventListeners();
            ISOActivityLog::clearBootedModels();
        }
    }

    public static function referenceFaults(): array
    {
        return ['unchanged references' => ['none'], 'balance changed' => ['balance'], 'reference moved' => ['moved'], 'reference deleted' => ['deleted']];
    }

    /** @return array{VAPLab,User,InventoryItemWarehouse,InventoryItemWarehouse} */
    private function fixture(bool $archived = false): array
    {
        $this->freezeTime();
        $lab = VAPLab::factory()->create();
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->withSession(['active_lab_id' => $lab->id]);
        $first = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'First']);
        $second = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Second']);
        if ($archived) {
            $first->delete();
            $second->delete();
        }

        return [$lab, $user, $first, $second];
    }

    /** @return array<string,list<array<string,mixed>>> */
    private function snapshot(): array
    {
        $result = [];
        foreach (['i_warehouses', 'inventory', 'activity_log', (new VAPLab)->getTable(), 'users', 'lab_user'] as $table) {
            $result[$table] = DB::table($table)->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
        }

        return $result;
    }
}
