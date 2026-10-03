<?php

namespace Tests\Feature;

use App\Actions\SaveInventoryWarehouse;
use App\Models\Inventory;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryItemLocation;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionType;
use App\Models\ISOActivityLog;
use App\Models\Permission;
use App\Models\ReagentConsumption;
use App\Models\ReagentConsumptionReversal;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InventoryWarehouseAuthoringTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('operations')]
    public function test_save_veto_is_not_reported_as_success_and_rolls_back(bool $creating): void
    {
        [$lab, $user, $warehouse, $location] = $this->fixture();
        $before = $this->snapshot();
        InventoryItemWarehouse::saving(fn (): bool => false);
        try {
            $this->submit($creating, $warehouse, $this->payload($location))->assertStatus(409);
            $this->assertSame($before, $this->snapshot());
        } finally {
            $this->clearListeners();
        }
    }

    #[DataProvider('invalidFlags')]
    public function test_missing_or_invalid_flags_are_rejected_not_coerced(bool $creating, string $field, mixed $value, bool $missing): void
    {
        [$lab, $user, $warehouse, $location] = $this->fixture();
        $data = $this->payload($location);
        if ($missing) {
            unset($data[$field]);
        } else {
            $data[$field] = $value;
        }
        $before = $this->snapshot();
        $this->submit($creating, $warehouse, $data)->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertSame($before, $this->snapshot());
    }

    public static function invalidFlags(): array
    {
        $cases = [];
        foreach ([true, false] as $creating) {
            foreach (['is_refrigerated', 'is_ventilated', 'has_air_exhaustion'] as $field) {
                foreach (['missing' => [null, true], 'null' => [null, false], 'number' => [2, false], 'text' => ['invalid', false], 'array' => [[], false]] as $name => [$value, $missing]) {
                    $cases[($creating ? 'create' : 'update').'-'.$field.'-'.$name] = [$creating, $field, $value, $missing];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('booleanTransports')]
    public function test_valid_boolean_transport_and_location_options_are_preserved(mixed $value, bool $expected): void
    {
        [$lab, $user, $warehouse, $location] = $this->fixture();
        $data = $this->payload($location);
        foreach (['is_refrigerated', 'is_ventilated', 'has_air_exhaustion'] as $field) {
            $data[$field] = $value;
        }
        $data['location_id'] = ['value' => (string) $location->id, 'label' => 'Selected location'];
        $data['lab_id'] = VAPLab::factory()->create()->id;
        $this->submit(true, $warehouse, $data)->assertRedirect();
        $created = InventoryItemWarehouse::query()->where('lab_id', $lab->id)->where('name', $data['name'])->sole();
        $this->assertSame($location->id, $created->location_id);
        foreach (['is_refrigerated', 'is_ventilated', 'has_air_exhaustion'] as $field) {
            $this->assertSame($expected, $created->{$field});
        }
        $audit = ISOActivityLog::withoutGlobalScopes()->where('subject_type', $created->getMorphClass())->where('subject_id', $created->id)->sole();
        $this->assertSame('created', $audit->event);
        $this->assertSame($lab->id, $audit->properties['lab_id']);
    }

    public static function booleanTransports(): array
    {
        return ['true' => [true, true], 'false' => [false, false], 'one' => [1, true], 'zero' => [0, false], 'string one' => ['1', true], 'string zero' => ['0', false]];
    }

    #[DataProvider('updateRoutes')]
    public function test_both_update_routes_preserve_owner_stock_and_history_and_replay_without_writing(string $route): void
    {
        [$lab, $user, $warehouse, $location] = $this->fixture();
        $retained = activity('inventory_warehouse')->causedBy($user)->performedOn($warehouse)->log('Retained evidence');
        $before = $this->snapshot();
        $data = $this->payload($location);
        $this->putJson(route($route, $warehouse->id), $data)->assertRedirect();
        $fresh = $warehouse->fresh();
        $this->assertSame($lab->id, $fresh->lab_id);
        $this->assertSame($data['name'], $fresh->name);
        $this->assertSame($before['inventory'], $this->snapshot()['inventory']);
        $this->assertSame($before['activity_log'][0], (array) DB::table('activity_log')->where('id', $retained->id)->first());
        $this->assertSame(2, ISOActivityLog::withoutGlobalScopes()->where('subject_id', $warehouse->id)->where('subject_type', $warehouse->getMorphClass())->count());
        $saved = 0;
        InventoryItemWarehouse::saving(function () use (&$saved): void {
            $saved++;
        });
        try {
            $snapshot = $this->snapshot();
            $this->putJson(route($route, $warehouse->id), $data)->assertRedirect();
            $this->assertSame(0, $saved);
            $this->assertSame($snapshot, $this->snapshot());
        } finally {
            $this->clearListeners();
        }
    }

    public static function updateRoutes(): array
    {
        return ['legacy parameter' => ['iwarehouses.update'], 'canonical parameter' => ['vap-inventory.master.warehouses.update']];
    }

    #[DataProvider('writeFaults')]
    public function test_successful_collateral_and_audit_faults_are_rejected_atomically(bool $creating, string $fault): void
    {
        [$lab, $user, $warehouse, $location] = $this->fixture();
        $retained = activity('inventory_warehouse')->causedBy($user)->performedOn($warehouse)->log('Retained evidence');
        $before = $this->snapshot();
        ISOActivityLog::creating(function (ISOActivityLog $entry) use ($fault, $lab, $user, $warehouse, $location, $retained): ?bool {
            if ($entry->log_name !== 'inventory_warehouse') {
                return null;
            }
            match ($fault) {
                'audit' => $entry->description = 'Altered audit',
                'audit-veto' => null,
                'root' => DB::table('i_warehouses')->where('id', $entry->subject_id)->update(['name' => 'Altered root']),
                'sibling' => DB::table('i_warehouses')->where('id', $warehouse->id)->update(['name' => 'Altered retained root']),
                'history' => DB::table('activity_log')->where('id', $retained->id)->update(['subject_id' => 2147483647]),
                'stock' => DB::table('inventory')->where('warehouse_id', $warehouse->id)->update(['qty_available' => '9.9999']),
                'moved-stock' => DB::table('inventory')->where('warehouse_id', $warehouse->id)->update(['deleted_at' => now()]),
                'location' => DB::table('i_locations')->where('id', $location->id)->update(['name' => 'Altered location']),
                'actor' => DB::table('users')->where('id', $user->id)->update(['name' => 'Altered actor']),
                'lab' => DB::table('labs')->where('id', $lab->id)->update(['name' => 'Altered lab']),
                'membership' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->delete(),
                'verification' => DB::table('users')->where('id', $user->id)->update(['email_verified_at' => null]),
                'pivot' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->update(['can_manage_branding' => true]),
            };

            return $fault === 'audit-veto' ? false : null;
        });
        try {
            $this->submit($creating, $warehouse, $this->payload($location))
                ->assertStatus(in_array($fault, ['membership', 'verification'], true) ? 403 : 409);
            $this->assertSame($before, $this->snapshot());
        } finally {
            $this->clearListeners();
        }
    }

    public static function writeFaults(): array
    {
        $cases = [];
        foreach ([true, false] as $creating) {
            foreach (['audit', 'audit-veto', 'root', 'sibling', 'history', 'stock', 'moved-stock', 'location', 'actor', 'lab', 'membership', 'verification', 'pivot'] as $fault) {
                $cases[($creating ? 'create' : 'update').'-'.$fault] = [$creating, $fault];
            }
        }

        return $cases;
    }

    public function test_active_name_uniqueness_is_lab_scoped_and_archived_names_can_be_reused(): void
    {
        [$lab, $user, $warehouse, $location] = $this->fixture();
        $data = $this->payload($location);
        $data['name'] = $warehouse->name;
        $this->submit(true, $warehouse, $data)->assertUnprocessable()->assertJsonValidationErrors('name');
        $warehouse->delete();
        InventoryItemWarehouse::query()->create(['lab_id' => VAPLab::factory()->create()->id, 'name' => $data['name']]);
        $this->submit(true, $warehouse, $data)->assertRedirect();
        $this->assertSame(1, InventoryItemWarehouse::query()->where('lab_id', $lab->id)->where('name', $data['name'])->count());
    }

    public function test_updates_reject_foreign_and_archived_warehouses_but_keep_an_unchanged_archived_location(): void
    {
        [$lab, $user, $warehouse, $location] = $this->fixture();
        $peer = InventoryItemWarehouse::query()->create(['lab_id' => VAPLab::factory()->create()->id, 'name' => 'Peer']);
        $this->putJson(route('iwarehouses.update', $peer))->assertNotFound();
        $data = $this->payload($location);
        $location->delete();
        $this->submit(false, $warehouse, $data)->assertRedirect();
        $this->submit(true, $warehouse, $data)->assertUnprocessable()->assertJsonValidationErrors('location_id');
        $warehouse->delete();
        $this->submit(false, $warehouse, $data)->assertNotFound();
    }

    public static function operations(): array
    {
        return ['create' => [true], 'update' => [false]];
    }

    #[DataProvider('operations')]
    public function test_successful_save_hooks_cannot_replace_the_frozen_metadata_or_timestamps(bool $creating): void
    {
        [$lab, $user, $warehouse, $location] = $this->fixture();
        $before = $this->snapshot();
        InventoryItemWarehouse::saving(function (InventoryItemWarehouse $record): void {
            $record->name = 'Altered authored name';
            $record->setUpdatedAt(now()->subDay());
        });
        try {
            $this->submit($creating, $warehouse, $this->payload($location))->assertStatus(409);
            $this->assertSame($before, $this->snapshot());
        } finally {
            $this->clearListeners();
        }
    }

    #[DataProvider('authorityReads')]
    public function test_fresh_authority_reads_cannot_change_retained_evidence_even_on_noop(string $operation, string $fault, int $read): void
    {
        [$lab, $user, $warehouse, $location] = $this->fixture();
        $data = $this->payload($location);
        if ($operation === 'noop') {
            $warehouse->update($data);
        }
        $audit = activity('inventory_warehouse')->causedBy($user)->performedOn($warehouse)->log('Retained');
        $before = $this->snapshot();
        $reads = 0;
        User::retrieved(function (User $actor) use ($user, $lab, $location, $audit, $read, $fault, &$reads): void {
            if ($actor->id !== $user->id || ++$reads !== $read) {
                return;
            }
            match ($fault) {
                'history' => DB::table('activity_log')->where('id', $audit->id)->update(['description' => 'Altered retained history']),
                'location' => DB::table('i_locations')->where('id', $location->id)->update(['name' => 'Altered lookup']),
                'membership' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->delete(),
            };
        });
        try {
            app(SaveInventoryWarehouse::class)->execute($lab->id, $user->id, $data, $operation === 'create' ? null : $warehouse->id);
            $this->fail('The authority-read fault must reject the operation.');
        } catch (HttpException $exception) {
            $this->assertSame($fault === 'membership' ? 403 : 409, $exception->getStatusCode());
            $this->assertSame($before, $this->snapshot());
        } finally {
            User::flushEventListeners();
            User::clearBootedModels();
        }
    }

    public static function authorityReads(): array
    {
        $cases = [];
        foreach (['create', 'update', 'noop'] as $operation) {
            foreach (['history', 'location', 'membership'] as $fault) {
                foreach ([1, 2] as $read) {
                    $cases[$operation.'-'.$fault.'-'.$read] = [$operation, $fault, $read];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('derivedFaults')]
    public function test_metadata_saves_preserve_retained_batches_and_reversal_evidence(string $operation, string $fault, bool $separateConsumptionWarehouse = false): void
    {
        [$lab, $user, $warehouse, $location] = $this->fixture();
        $stock = Inventory::query()->where('warehouse_id', $warehouse->id)->sole();
        $batch = InventoryBatch::query()->create(['lab_id' => $lab->id, 'inventory_id' => $stock->id,
            'batch_number' => 'Retained batch', 'qty_received' => '0.1250', 'qty_remaining' => '0.1250', 'received_date' => today()]);
        $type = InventoryTransactionType::query()->create(['code' => 'warehouse-evidence', 'name' => 'Evidence']);
        $movement = InventoryTransaction::query()->create(['lab_id' => $lab->id, 'inventory_id' => $stock->id,
            'warehouse_id' => $warehouse->id, 'item_id' => $stock->item_id, 'type_id' => $type->id,
            'user_id' => $user->id, 'qty' => '0.1250', 'reason' => 'Retained movement']);
        $consumptionWarehouse = $separateConsumptionWarehouse
            ? InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Separate retained consumption']) : $warehouse;
        $consumption = ReagentConsumption::query()->create(['lab_id' => $lab->id, 'warehouse_id' => $consumptionWarehouse->id,
            'reagent_id' => $stock->item_id, 'reagent_name' => 'Material', 'quantity_used' => '0.1250', 'used_by' => 'Technician',
            'used_at' => now(), 'date' => today(), 'user_id' => $user->id]);
        $reversal = ReagentConsumptionReversal::factory()->forConsumption($consumption, $movement)->create(['user_id' => $user->id]);
        if ($operation === 'restore') {
            $warehouse->delete();
            $user->givePermissionTo(Permission::findOrCreate('restore_iwarehouses', 'web'));
        }
        $before = $this->snapshot();
        ISOActivityLog::creating(function (ISOActivityLog $entry) use ($fault, $batch, $reversal): void {
            if ($entry->log_name !== 'inventory_warehouse') {
                return;
            }
            match ($fault) {
                'batch-quantity' => DB::table($batch->getTable())->where('id', $batch->id)->update(['qty_remaining' => '0.0000']),
                'batch-deleted' => DB::table($batch->getTable())->where('id', $batch->id)->delete(),
                'reversal-date' => DB::table($reversal->getTable())->where('id', $reversal->id)->update(['reversed_at' => now()->subDay()]),
                'reversal-deleted' => DB::table($reversal->getTable())->where('id', $reversal->id)->delete(),
            };
        });
        try {
            $response = $operation === 'restore'
                ? $this->patchJson(route('iwarehouses.restore'), ['recordIds' => [$warehouse->id]])
                : $this->submit($operation === 'create', $warehouse, $this->payload($location));
            $response->assertStatus(409);
            $this->assertSame($before, $this->snapshot());
        } finally {
            $this->clearListeners();
        }
    }

    public static function derivedFaults(): array
    {
        $cases = [];
        foreach (['create', 'update', 'restore'] as $operation) {
            foreach (['batch-quantity', 'batch-deleted', 'reversal-date', 'reversal-deleted'] as $fault) {
                $cases[$operation.'-'.$fault] = [$operation, $fault];
            }
        }
        $cases['restore-reversal-linked-by-movement-date'] = ['restore', 'reversal-date', true];
        $cases['restore-reversal-linked-by-movement-deleted'] = ['restore', 'reversal-deleted', true];

        return $cases;
    }

    /** @return array{VAPLab,User,InventoryItemWarehouse,InventoryItemLocation} */
    private function fixture(): array
    {
        $this->freezeTime();
        $lab = VAPLab::factory()->create();
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        foreach (['view_iwarehouses', 'add_iwarehouses', 'edit_iwarehouses'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $location = InventoryItemLocation::query()->create(['name' => 'Location']);
        $warehouse = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Existing', 'location_id' => $location->id]);
        $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Material']);
        Inventory::query()->create(['lab_id' => $lab->id, 'item_id' => $item->id, 'warehouse_id' => $warehouse->id,
            'qty_available' => '0.1250', 'min_stock_level' => '0.0000', 'reorder_point' => '0.0000', 'status' => 'AVAILABLE']);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        return [$lab, $user, $warehouse, $location];
    }

    /** @return array<string,mixed> */
    private function payload(InventoryItemLocation $location): array
    {
        return ['name' => 'Authored warehouse', 'location_id' => $location->id, 'is_refrigerated' => true,
            'is_ventilated' => false, 'has_air_exhaustion' => true];
    }

    /** @param array<string,mixed> $data */
    private function submit(bool $creating, InventoryItemWarehouse $warehouse, array $data): TestResponse
    {
        return $creating ? $this->postJson(route('iwarehouses.store'), $data) : $this->putJson(route('iwarehouses.update', $warehouse), $data);
    }

    /** @return array<string,list<array<string,mixed>>> */
    private function snapshot(): array
    {
        $result = [];
        foreach (['i_warehouses', 'i_locations', 'inventory', 'activity_log', 'users', 'lab_user', 'labs',
            'i_inventory_batches', 'itransactions', 'reagent_consumption', 'reagent_consumption_reversals'] as $table) {
            $result[$table] = DB::table($table)->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
        }

        return $result;
    }

    private function clearListeners(): void
    {
        foreach ([InventoryItemWarehouse::class, ISOActivityLog::class] as $class) {
            $class::flushEventListeners();
            $class::clearBootedModels();
        }
    }
}
