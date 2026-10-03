<?php

namespace Tests\Feature;

use App\Actions\AdjustInventoryItemStock;
use App\Actions\ConsumeInventoryReagent;
use App\Actions\CreateInventoryItem;
use App\Actions\CreateInventoryPosition;
use App\Actions\ManageInventoryTransfers;
use App\Actions\MutateInventoryPositions;
use App\Actions\ReverseInventoryReagentConsumption;
use App\Actions\SaveInventoryWarehouse;
use App\Actions\SetInventoryWarehousesArchived;
use App\Actions\UpdateInventoryItem;
use App\Models;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\IsolatedPostgresTestCase;

class InventoryTransferConcurrencyTest extends IsolatedPostgresTestCase
{
    public function test_competing_warehouse_creations_cannot_publish_duplicate_active_names(): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        $operation = $this->warehouseOperation($lab, $operator, $item, $source, $destination, true);
        $statuses = $this->compete([$lab], [$operation, $operation]);
        sort($statuses);
        $this->assertSame([200, 422], $statuses);
        $this->assertSame(1, Models\InventoryItemWarehouse::query()->where('lab_id', $lab->id)->where('name', $operation['warehouse_data']['name'])->count());
        $this->assertSame(1, Models\ISOActivityLog::withoutGlobalScopes()->where('log_name', 'inventory_warehouse')->count());
    }

    public function test_competing_identical_warehouse_edits_publish_one_revision(): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        $operation = $this->warehouseOperation($lab, $operator, $item, $source, $destination);
        $before = $this->snapshot();
        $this->assertSame([200, 200], $this->compete([$lab], [$operation, $operation]));
        $this->assertSame($operation['warehouse_data']['name'], $destination->fresh()->name);
        $this->assertSame(1, Models\ISOActivityLog::withoutGlobalScopes()->where('log_name', 'inventory_warehouse')->count());
        $this->assertSame($before, $this->snapshot());
    }

    #[DataProvider('warehouseAuthoringRevocations')]
    public function test_waiting_warehouse_authoring_rechecks_current_authority(bool $creating, string $revocation): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        $operation = $this->warehouseOperation($lab, $operator, $item, $source, $destination, $creating);
        $before = $this->warehouseSnapshot();
        $this->assertSame([403], $this->compete([$lab], [$operation], function () use ($revocation, $lab, $operator, $creating): void {
            match ($revocation) {
                'membership' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $operator->id)->delete(),
                'permission' => $operator->revokePermissionTo($creating ? 'add_iwarehouses' : 'edit_iwarehouses'),
                'verification' => DB::table('users')->where('id', $operator->id)->update(['email_verified_at' => null]),
                'activation' => DB::table('users')->where('id', $operator->id)->update(['is_active' => false]),
                'lab archive' => DB::table($lab->getTable())->where('id', $lab->id)->update(['deleted_at' => now()]),
            };
        }));
        $this->assertSame($before, $this->warehouseSnapshot());
    }

    public static function warehouseAuthoringRevocations(): array
    {
        $cases = [];
        foreach ([true, false] as $creating) {
            foreach (['membership', 'permission', 'verification', 'activation', 'lab archive'] as $revocation) {
                $cases[($creating ? 'create' : 'update').'-'.$revocation] = [$creating, $revocation];
            }
        }

        return $cases;
    }

    #[DataProvider('warehouseOrderings')]
    public function test_warehouse_metadata_and_archive_observe_the_committed_winner(bool $archiveFirst): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        $operator->givePermissionTo(Models\Permission::findOrCreate('delete_iwarehouses', 'web'));
        $edit = $this->warehouseOperation($lab, $operator, $item, $source, $destination);
        $archive = ['stage' => 'warehouse-archive', 'recordIds' => [$destination->id]] + $this->operation($lab, $operator, $item, $source, $destination);
        $before = $this->snapshot();
        $this->assertSame([$archiveFirst ? 404 : 200], $this->compete([$lab], [$archiveFirst ? $edit : $archive],
            fn () => $this->runOperation($archiveFirst ? $archive : $edit)));
        $fresh = $destination->fresh();
        $this->assertNotNull($fresh->deleted_at);
        $this->assertSame($archiveFirst ? 'Destination' : $edit['warehouse_data']['name'], $fresh->name);
        $this->assertSame($before, $this->snapshot());
        $this->assertSame($archiveFirst ? 1 : 2, Models\ISOActivityLog::withoutGlobalScopes()->where('log_name', 'inventory_warehouse')->count());
    }

    #[DataProvider('operations')]
    public function test_warehouse_authoring_respects_real_outer_commit_and_rollback(bool $creating): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        $operation = $this->warehouseOperation($lab, $operator, $item, $source, $destination, $creating);
        $observerName = 'warehouse_observer_'.$this->schema;
        config(['database.connections.'.$observerName => DB::connection()->getConfig()]);
        $observer = DB::connection($observerName);
        try {
            $before = $this->warehouseSnapshot($observer);
            DB::beginTransaction();
            $this->runOperation($operation);
            $this->assertSame($before, $this->warehouseSnapshot($observer));
            $this->assertNotSame($before, $this->warehouseSnapshot());
            DB::rollBack();
            $this->assertSame($before, $this->warehouseSnapshot());
            DB::beginTransaction();
            $this->runOperation($operation);
            $expected = $this->warehouseSnapshot();
            $this->assertSame($before, $this->warehouseSnapshot($observer));
            DB::commit();
            $this->assertSame($expected, $this->warehouseSnapshot($observer));
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge($observerName);
            config(['database.connections.'.$observerName => null]);
        }
    }

    public static function operations(): array
    {
        return ['create' => [true], 'update' => [false]];
    }

    private function warehouseOperation(Models\VAPLab $lab, Models\User $operator, Models\InventoryItem $item,
        Models\InventoryItemWarehouse $source, Models\InventoryItemWarehouse $destination, bool $creating = false): array
    {
        $operator->givePermissionTo(Models\Permission::findOrCreate($creating ? 'add_iwarehouses' : 'edit_iwarehouses', 'web'));
        $location = Models\InventoryItemLocation::query()->create(['name' => 'Warehouse location']);

        return ['stage' => 'warehouse-save', 'warehouse_id' => $creating ? null : $destination->id,
            'warehouse_data' => ['name' => 'Authored warehouse', 'location_id' => $location->id, 'is_refrigerated' => true,
                'is_ventilated' => false, 'has_air_exhaustion' => true]] + $this->operation($lab, $operator, $item, $source, $destination);
    }

    private function warehouseSnapshot(?ConnectionInterface $connection = null): array
    {
        $connection ??= DB::connection();
        $snapshot = $this->snapshot($connection);
        foreach (['i_warehouses', 'i_locations', 'activity_log'] as $table) {
            $snapshot[$table] = $connection->table($table)->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
        }

        return $snapshot;
    }

    public function test_competing_consumptions_cannot_overspend_available_stock(): void
    {
        [$lab, $operator, $item, $source, $destination, $stock] = $this->fixture();
        $operation = $this->consumptionOperation($lab, $operator, $item, $source, $destination);
        $operation['consumption_data']['quantity_used'] = '6.8767';
        $statuses = $this->compete([$lab], [$operation, $operation]);
        sort($statuses);
        $this->assertSame([200, 422], $statuses);
        $this->assertSame('3.1233', $stock->fresh()->qty_available);
        $this->assertSame(1, Models\ReagentConsumption::query()->count());
        $this->assertSame('-6.8767', Models\InventoryTransaction::query()->sole()->qty);
    }

    public function test_competing_consumption_and_stock_adjustment_share_the_available_balance(): void
    {
        [$lab, $operator, $item, $source, $destination, $stock] = $this->fixture();
        $consumption = $this->consumptionOperation($lab, $operator, $item, $source, $destination);
        $consumption['consumption_data']['quantity_used'] = '6.8767';
        $operator->givePermissionTo(Models\Permission::findOrCreate('edit_inventory', 'web'));
        $adjustment = $this->adjustment($lab, $operator, $item, $source, $destination, 'remove', '6.8767');
        $statuses = $this->compete([$lab], [$consumption, $adjustment]);
        sort($statuses);
        $this->assertSame([200, 422], $statuses);
        $this->assertSame('3.1233', $stock->fresh()->qty_available);
        $this->assertSame(1, Models\InventoryTransaction::query()->count());
    }

    public function test_competing_reversal_requests_retain_one_reversal_and_credit_stock_once(): void
    {
        [$lab, $operator, $item, $source, $destination, $stock] = $this->fixture();
        $operation = $this->consumptionOperation($lab, $operator, $item, $source, $destination, 'reverse');
        $before = (array) DB::table('reagent_consumption')->where('id', $operation['consumption_id'])->first();
        $this->assertSame([200, 200], $this->compete([$lab], [$operation, $operation]));
        $this->assertSame('10.0000', $stock->fresh()->qty_available);
        $this->assertSame(1, Models\ReagentConsumptionReversal::query()->count());
        $this->assertSame(2, Models\InventoryTransaction::query()->count());
        $this->assertSame($before, (array) DB::table('reagent_consumption')->where('id', $operation['consumption_id'])->first());
        $this->assertSame('0.1250', Models\ReagentConsumptionReversal::query()->sole()->inventoryTransaction->qty);
    }

    public function test_two_labs_can_create_the_absent_shared_consumption_type_without_partial_writes(): void
    {
        [$firstLab, $firstOperator, $firstItem, $firstSource, $firstDestination, $firstStock] = $this->fixture();
        [$secondLab, $secondOperator, $secondItem, $secondSource, $secondDestination, $secondStock] = $this->fixture();
        $first = $this->consumptionOperation($firstLab, $firstOperator, $firstItem, $firstSource, $firstDestination);
        $second = $this->consumptionOperation($secondLab, $secondOperator, $secondItem, $secondSource, $secondDestination);
        $first['type_barrier'] = $second['type_barrier'] = true;
        $first['type_code'] = $second['type_code'] = 'consumption';
        $this->assertSame([200, 200], $this->compete([$firstLab, $secondLab], [$first, $second]));
        $this->assertSame(1, Models\InventoryTransactionType::query()->where('code', 'consumption')->count());
        $this->assertSame(2, Models\ReagentConsumption::query()->count());
        $this->assertSame('9.8750', $firstStock->fresh()->qty_available);
        $this->assertSame('9.8750', $secondStock->fresh()->qty_available);
    }

    #[DataProvider('consumptionStages')]
    public function test_waiting_consumption_workflow_rechecks_its_separate_permission(string $stage): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        $operation = $this->consumptionOperation($lab, $operator, $item, $source, $destination, $stage);
        $before = $this->snapshot();
        $this->assertSame([403], $this->compete([$lab], [$operation], function () use ($operator, $stage): void {
            $operator->revokePermissionTo($stage === 'consume' ? 'add_reagent_consumption' : 'delete_reagent_consumption');
        }));
        $this->assertSame($before, $this->snapshot());
    }

    public static function consumptionStages(): array
    {
        return ['record' => ['consume'], 'reverse' => ['reverse']];
    }

    public function test_empty_reversal_migration_round_trip_preserves_issued_consumption(): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        $operation = $this->consumptionOperation($lab, $operator, $item, $source, $destination);
        $this->runOperation($operation);
        $before = $this->snapshot();
        $migration = require database_path('migrations/2026_10_02_222759_create_reagent_consumption_reversals_table.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('reagent_consumption_reversals'));
        $migration->up();
        $this->assertSame($before, $this->snapshot());
    }

    #[DataProvider('adjustmentRevocations')]
    public function test_waiting_catalogue_edit_rechecks_current_authority(string $revocation): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        $operator->givePermissionTo(Models\Permission::findOrCreate('edit_iitems', 'web'));
        $operation = ['stage' => 'item-update', 'item_data' => ['name' => 'Concurrent metadata edit', 'category_id' => $item->category_id, 'unit_id' => $item->unit_id]]
            + $this->operation($lab, $operator, $item, $source, $destination);
        $before = $this->snapshot();
        $this->assertSame([403], $this->compete([$lab], [$operation], function () use ($revocation, $lab, $operator): void {
            match ($revocation) {
                'membership' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $operator->id)->delete(),
                'permission' => $operator->revokePermissionTo('edit_iitems'),
                'verification' => DB::table('users')->where('id', $operator->id)->update(['email_verified_at' => null]),
                'activation' => DB::table('users')->where('id', $operator->id)->update(['is_active' => false]),
                'lab archive' => DB::table($lab->getTable())->where('id', $lab->id)->update(['deleted_at' => now()]),
            };
        }));
        $this->assertSame($before, $this->snapshot());
    }

    public function test_competing_partial_catalogue_edits_preserve_both_committed_metadata_changes(): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        $operator->givePermissionTo(Models\Permission::findOrCreate('edit_iitems', 'web'));
        $operation = ['stage' => 'item-update', 'item_data' => ['name' => 'Concurrent metadata edit', 'category_id' => $item->category_id, 'unit_id' => $item->unit_id]]
            + $this->operation($lab, $operator, $item, $source, $destination);
        $first = $operation;
        $first['item_data']['brand'] = 'Committed brand';
        $second = $operation;
        $second['item_data']['description'] = 'Committed description';
        $before = $this->snapshot();
        $this->assertSame([200, 200], $this->compete([$lab], [$first, $second]));
        $this->assertSame('Committed brand', $item->fresh()->brand);
        $this->assertSame('Committed description', $item->fresh()->description);
        $after = $this->snapshot();
        unset($before['i_items'], $after['i_items']);
        $this->assertSame($before, $after);
    }

    public function test_competing_catalogue_creations_issue_distinct_scoped_sequences(): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        $operator->givePermissionTo(Models\Permission::findOrCreate('add_iitems', 'web'));
        $operation = $this->catalogueCreation($lab, $operator, $item, $source, $destination);
        $beforeSequence = (int) $item->seq;
        $this->assertSame([200, 200, 200], $this->compete([$lab], [$operation, $operation, $operation]));
        $created = Models\InventoryItem::query()->where('name', 'Concurrent catalogue item')->orderByRaw('CAST(seq AS BIGINT)')->get();
        $this->assertSame([$beforeSequence + 1, $beforeSequence + 2, $beforeSequence + 3], $created->map(fn ($record): int => (int) $record->seq)->all());
        $this->assertCount(3, $created->pluck('internal_code')->unique());
        $this->assertSame(['0.1250'], Models\InventoryTransaction::query()->pluck('qty')->unique()->all());
        $this->assertSame(3, Models\InventoryTransaction::query()->count());
    }

    public function test_competing_catalogue_codes_have_one_validation_winner(): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        $operator->givePermissionTo(Models\Permission::findOrCreate('add_iitems', 'web'));
        $operation = $this->catalogueCreation($lab, $operator, $item, $source, $destination);
        $operation['item_data']['code'] = 'CONCURRENT-CODE';
        $statuses = $this->compete([$lab], [$operation, $operation]);
        sort($statuses);
        $this->assertSame([200, 422], $statuses);
        $this->assertSame(1, Models\InventoryItem::query()->where('code', 'CONCURRENT-CODE')->count());
        $this->assertSame(1, Models\InventoryTransaction::query()->count());
    }

    public function test_independent_labs_can_create_the_same_missing_catalogue_opening_type(): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        [$peer, $peerOperator, $peerItem, $peerSource, $peerDestination] = $this->fixture();
        foreach ([$operator, $peerOperator] as $actor) {
            $actor->givePermissionTo(Models\Permission::findOrCreate('add_iitems', 'web'));
        }
        $first = $this->catalogueCreation($lab, $operator, $item, $source, $destination) + ['type_barrier' => true, 'type_code' => 'stock_in'];
        $second = $this->catalogueCreation($peer, $peerOperator, $peerItem, $peerSource, $peerDestination) + ['type_barrier' => true, 'type_code' => 'stock_in'];
        $this->assertSame([200, 200], $this->compete([$lab, $peer], [$first, $second]));
        $this->assertSame(1, Models\InventoryTransactionType::query()->where('code', 'stock_in')->count());
        $this->assertSame(2, Models\InventoryTransaction::query()->count());
    }

    #[DataProvider('adjustmentRevocations')]
    public function test_waiting_catalogue_creation_rechecks_current_authority(string $revocation): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        $operator->givePermissionTo(Models\Permission::findOrCreate('add_iitems', 'web'));
        $operation = $this->catalogueCreation($lab, $operator, $item, $source, $destination);
        $before = $this->snapshot();
        $this->assertSame([403], $this->compete([$lab], [$operation], function () use ($revocation, $lab, $operator): void {
            match ($revocation) {
                'membership' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $operator->id)->delete(),
                'permission' => $operator->revokePermissionTo('add_iitems'),
                'verification' => DB::table('users')->where('id', $operator->id)->update(['email_verified_at' => null]),
                'activation' => DB::table('users')->where('id', $operator->id)->update(['is_active' => false]),
                'lab archive' => DB::table($lab->getTable())->where('id', $lab->id)->update(['deleted_at' => now()]),
            };
        }));
        $this->assertSame($before, $this->snapshot());
    }

    #[DataProvider('warehouseOrderings')]
    public function test_catalogue_creation_and_warehouse_archive_follow_the_committed_winner(bool $archiveFirst): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        foreach (['add_iitems', 'delete_iwarehouses'] as $permission) {
            $operator->givePermissionTo(Models\Permission::findOrCreate($permission, 'web'));
        }
        $create = $this->catalogueCreation($lab, $operator, $item, $source, $destination);
        $archive = ['stage' => 'warehouse-archive', 'recordIds' => [$destination->id]] + $create;
        $this->assertSame([422], $this->compete([$lab], [$archiveFirst ? $create : $archive], function () use ($archiveFirst, $create, $archive): void {
            $this->runOperation($archiveFirst ? $archive : $create);
        }));
        $this->assertSame($archiveFirst, $destination->fresh()->trashed());
        $this->assertSame($archiveFirst ? 0 : 1, Models\InventoryItem::query()->where('name', 'Concurrent catalogue item')->count());
        $this->assertSame($archiveFirst ? 0 : 1, Models\InventoryTransaction::query()->count());
    }

    private function catalogueCreation(Models\VAPLab $lab, Models\User $operator, Models\InventoryItem $item,
        Models\InventoryItemWarehouse $source, Models\InventoryItemWarehouse $destination): array
    {
        return ['stage' => 'item-create', 'item_data' => ['name' => 'Concurrent catalogue item',
            'category_id' => $item->category_id, 'unit_id' => $item->unit_id,
            'warehouses' => [['id' => $destination->id, 'qty_available' => '0.1250', 'min_stock_level' => '0.0001', 'reorder_point' => '0.0125']]]]
            + $this->operation($lab, $operator, $item, $source, $destination);
    }

    public function test_adjustment_and_transfer_compete_without_spending_the_same_fraction_twice(): void
    {
        [$lab, $operator, $item, $source, $destination, $stock] = $this->fixture();
        $operator->givePermissionTo(Models\Permission::findOrCreate('edit_inventory', 'web'));
        $adjustment = $this->adjustment($lab, $operator, $item, $source, $destination, 'remove', '6.8767');
        $results = $this->compete([$lab], [$adjustment, $this->operation($lab, $operator, $item, $source, $destination)]);
        $this->assertSame([200, 422], collect($results)->sort()->values()->all());
        $this->assertSame($results[0] === 200 ? '3.1233' : '6.8766', $stock->fresh()->qty_available);
        $this->assertSame($results[0] === 200 ? 0 : 1, Models\InventoryItemTransfer::query()->count());
        $this->assertSame(1, Models\InventoryTransaction::query()->count());
    }

    public function test_parallel_batch_adjustments_preserve_both_exact_balances(): void
    {
        [$lab, $operator, $item, $source, $destination, $stock] = $this->fixture();
        $operator->givePermissionTo(Models\Permission::findOrCreate('edit_inventory', 'web'));
        $batch = Models\InventoryBatch::query()->create(['lab_id' => $lab->id, 'inventory_id' => $stock->id,
            'batch_number' => 'Shared batch', 'qty_received' => '3.5000', 'qty_remaining' => '3.5000']);
        $operation = $this->adjustment($lab, $operator, $item, $source, $destination, 'add', '0.1250');
        $operation['adjustment']['batch_id'] = $batch->id;
        $this->assertSame([200, 200], $this->compete([$lab], [$operation, $operation]));
        $this->assertSame('10.2500', $stock->fresh()->qty_available);
        $this->assertSame('3.7500', $batch->fresh()->qty_remaining);
        $this->assertSame('3.5000', $batch->fresh()->qty_received);
        $this->assertSame(2, Models\InventoryTransaction::query()->where('batch_id', $batch->id)->count());
    }

    public function test_two_labs_can_create_the_same_missing_adjustment_type(): void
    {
        [$lab, $operator, $item, $source, $destination, $stock] = $this->fixture();
        [$peer, $peerOperator, $peerItem, $peerSource, $peerDestination, $peerStock] = $this->fixture();
        foreach ([$operator, $peerOperator] as $actor) {
            $actor->givePermissionTo(Models\Permission::findOrCreate('edit_inventory', 'web'));
        }
        $first = $this->adjustment($lab, $operator, $item, $source, $destination, 'add', '0.1250') + ['type_barrier' => true, 'type_code' => 'stock_adjustment_add'];
        $second = $this->adjustment($peer, $peerOperator, $peerItem, $peerSource, $peerDestination, 'add', '0.1250') + ['type_barrier' => true, 'type_code' => 'stock_adjustment_add'];
        $this->assertSame([200, 200], $this->compete([$lab, $peer], [$first, $second]));
        $this->assertSame('10.1250', $stock->fresh()->qty_available);
        $this->assertSame('10.1250', $peerStock->fresh()->qty_available);
        $this->assertSame(1, Models\InventoryTransactionType::query()->where('code', 'stock_adjustment_add')->count());
        $this->assertSame(2, Models\InventoryTransaction::query()->count());
    }

    #[DataProvider('adjustmentRevocations')]
    public function test_waiting_adjustment_rechecks_fresh_authority(string $revocation): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        $operator->givePermissionTo(Models\Permission::findOrCreate('edit_inventory', 'web'));
        $operation = $this->adjustment($lab, $operator, $item, $source, $destination, 'add', '0.1250');
        $before = $this->snapshot();
        $this->assertSame([403], $this->compete([$lab], [$operation], function () use ($revocation, $lab, $operator): void {
            match ($revocation) {
                'membership' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $operator->id)->delete(),
                'permission' => $operator->revokePermissionTo('edit_inventory'),
                'verification' => DB::table($operator->getTable())->where('id', $operator->id)->update(['email_verified_at' => null]),
                'activation' => DB::table($operator->getTable())->where('id', $operator->id)->update(['is_active' => false]),
                'lab archive' => DB::table($lab->getTable())->where('id', $lab->id)->update(['deleted_at' => now()]),
            };
        }));
        $this->assertSame($before, $this->snapshot());
    }

    public static function adjustmentRevocations(): array
    {
        return array_combine(['membership', 'permission', 'verification', 'activation', 'lab archive'],
            [['membership'], ['permission'], ['verification'], ['activation'], ['lab archive']]);
    }

    #[DataProvider('warehouseOrderings')]
    public function test_opening_position_and_warehouse_archive_observe_the_committed_winner(bool $archiveFirst): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        foreach (['add_inventory', 'delete_iwarehouses'] as $permission) {
            $operator->givePermissionTo(Models\Permission::findOrCreate($permission, 'web'));
        }
        $create = ['stage' => 'position-create', 'position' => ['item_id' => $item->id, 'warehouse_id' => $destination->id,
            'qty_available' => '0.0000', 'min_stock_level' => '0.1250', 'reorder_point' => '0.5000']] + $this->operation($lab, $operator, $item, $source, $destination);
        $archive = ['stage' => 'warehouse-archive', 'recordIds' => [$destination->id]] + $create;
        $this->assertSame([$archiveFirst ? 404 : 422], $this->compete([$lab], [$archiveFirst ? $create : $archive], function () use ($archiveFirst, $create, $archive): void {
            $this->runOperation($archiveFirst ? $archive : $create);
        }));
        $this->assertSame($archiveFirst, $destination->fresh()->trashed());
        $this->assertSame($archiveFirst ? 0 : 1, Models\Inventory::query()->where('warehouse_id', $destination->id)->count());
        $this->assertSame(0, Models\InventoryTransaction::query()->count());
    }

    private function adjustment(Models\VAPLab $lab, Models\User $operator, Models\InventoryItem $item, Models\InventoryItemWarehouse $source,
        Models\InventoryItemWarehouse $destination, string $type, string $quantity): array
    {
        return ['stage' => 'adjust', 'adjustment' => ['warehouse_id' => $source->id, 'adjustment_type' => $type,
            'quantity' => $quantity, 'reason' => 'Concurrent count']] + $this->operation($lab, $operator, $item, $source, $destination);
    }

    #[DataProvider('warehouseOrderings')]
    public function test_warehouse_archive_and_transfer_recheck_the_committed_winner(bool $archiveFirst): void
    {
        [$lab, $operator, $item, $source, $destination, $stock] = $this->fixture();
        $operator->givePermissionTo(Models\Permission::findOrCreate('delete_iwarehouses', 'web'));
        $transfer = $this->operation($lab, $operator, $item, $source, $destination);
        $archive = ['stage' => 'warehouse-archive', 'recordIds' => [$destination->id]] + $transfer;
        $this->assertSame([422], $this->compete([$lab], [$archiveFirst ? $transfer : $archive], function () use ($archiveFirst, $lab, $operator, $destination, $transfer): void {
            if ($archiveFirst) {
                app(SetInventoryWarehousesArchived::class)->execute($operator->id, $lab->id, [$destination->id], true);
            } else {
                $this->runOperation($transfer);
            }
        }));
        $this->assertSame($archiveFirst, Models\InventoryItemWarehouse::withTrashed()->findOrFail($destination->id)->trashed());
        $this->assertSame($archiveFirst ? '10.0000' : '6.8766', $stock->fresh()->qty_available);
        $this->assertSame($archiveFirst ? 0 : 1, Models\InventoryItemTransfer::query()->count());
        $this->assertSame($archiveFirst ? 0 : 1, Models\InventoryTransaction::query()->count());
        $this->assertSame($archiveFirst ? 1 : 0, Models\ISOActivityLog::withoutGlobalScopes()->where('log_name', 'inventory_warehouse')->count());
    }

    #[DataProvider('warehouseOrderings')]
    public function test_position_archive_and_adjustment_observe_the_committed_winner(bool $archiveFirst): void
    {
        [$lab, $operator, $item, $source, $destination, $stock] = $this->fixture();
        $operator->givePermissionTo(Models\Permission::findOrCreate('delete_inventory', 'web'),
            Models\Permission::findOrCreate('edit_inventory', 'web'));
        DB::table('inventory')->where('id', $stock->id)->update(['qty_available' => '0.0000']);
        $adjustment = $this->adjustment($lab, $operator, $item, $source, $destination, 'add', '0.1250');
        $archive = ['stage' => 'position-archive', 'recordIds' => [$stock->id]] + $this->operation($lab, $operator, $item, $source, $destination);
        $this->assertSame([$archiveFirst ? 404 : 422], $this->compete([$lab], [$archiveFirst ? $adjustment : $archive],
            fn () => $this->runOperation($archiveFirst ? $archive : $adjustment)));
        $retained = Models\Inventory::withTrashed()->findOrFail($stock->id);
        $this->assertSame($archiveFirst, $retained->trashed());
        $this->assertSame($archiveFirst ? '0.0000' : '0.1250', $retained->qty_available);
        $this->assertSame($archiveFirst ? 0 : 1, Models\InventoryTransaction::query()->count());
    }

    public static function warehouseOrderings(): array
    {
        return ['archive commits first' => [true], 'transfer commits first' => [false]];
    }

    public function test_restoring_a_warehouse_unblocks_a_waiting_transfer(): void
    {
        [$lab, $operator, $item, $source, $destination, $stock] = $this->fixture();
        $operator->givePermissionTo(Models\Permission::findOrCreate('restore_iwarehouses', 'web'));
        $destination->delete();
        $operation = $this->operation($lab, $operator, $item, $source, $destination);
        $this->assertSame([200], $this->compete([$lab], [$operation], function () use ($lab, $operator, $destination): void {
            app(SetInventoryWarehousesArchived::class)->execute($operator->id, $lab->id, [$destination->id], false);
        }));
        $this->assertNull($destination->fresh()->deleted_at);
        $this->assertSame('6.8766', $stock->fresh()->qty_available);
        $this->assertSame(1, Models\InventoryItemTransfer::query()->count());
        $this->assertSame(1, Models\ISOActivityLog::withoutGlobalScopes()->where('log_name', 'inventory_warehouse')->count());
    }

    public function test_reversed_warehouse_archive_batches_replay_without_duplicate_audits(): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        $operator->givePermissionTo(Models\Permission::findOrCreate('delete_iwarehouses', 'web'));
        $second = Models\InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Empty second']);
        $firstOperation = ['stage' => 'warehouse-archive', 'recordIds' => [$destination->id, $second->id]] + $this->operation($lab, $operator, $item, $source, $destination);
        $secondOperation = $firstOperation;
        $secondOperation['recordIds'] = array_reverse($firstOperation['recordIds']);
        $this->assertSame([200, 200], $this->compete([$lab], [$firstOperation, $secondOperation]));
        $this->assertSoftDeleted($destination);
        $this->assertSoftDeleted($second);
        $this->assertSame(2, Models\ISOActivityLog::withoutGlobalScopes()->where('log_name', 'inventory_warehouse')->count());
        $this->assertSame(0, Models\InventoryItemTransfer::query()->count());
    }

    #[DataProvider('warehouseRevocations')]
    public function test_waiting_warehouse_lifecycle_rechecks_current_authority(bool $archived, string $revocation): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        $permission = $archived ? 'delete_iwarehouses' : 'restore_iwarehouses';
        $operator->givePermissionTo(Models\Permission::findOrCreate($permission, 'web'));
        if (! $archived) {
            $destination->delete();
        }
        $before = (array) DB::table($destination->getTable())->find($destination->id);
        $operation = ['stage' => $archived ? 'warehouse-archive' : 'warehouse-restore', 'recordIds' => [$destination->id]] + $this->operation($lab, $operator, $item, $source, $destination);
        $this->assertSame([403], $this->compete([$lab], [$operation], function () use ($revocation, $permission, $operator, $lab): void {
            match ($revocation) {
                'membership' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $operator->id)->delete(),
                'permission' => $operator->revokePermissionTo($permission),
                'verification' => DB::table('users')->where('id', $operator->id)->update(['email_verified_at' => null]),
            };
        }));
        $this->assertSame($before, (array) DB::table($destination->getTable())->find($destination->id));
        $this->assertSame(0, Models\ISOActivityLog::withoutGlobalScopes()->where('log_name', 'inventory_warehouse')->count());
        $this->assertSame(0, Models\InventoryItemTransfer::query()->count());
    }

    public static function warehouseRevocations(): array
    {
        $cases = [];
        foreach ([true, false] as $archived) {
            foreach (['membership', 'permission', 'verification'] as $revocation) {
                $cases[($archived ? 'archive-' : 'restore-').$revocation] = [$archived, $revocation];
            }
        }

        return $cases;
    }

    public function test_competing_fractional_reservations_cannot_overspend_source_stock(): void
    {
        [$lab, $operator, $item, $source, $destination, $stock] = $this->fixture();
        $operation = $this->operation($lab, $operator, $item, $source, $destination);
        $statuses = $this->compete([$lab], array_fill(0, 4, $operation));
        sort($statuses);
        $this->assertSame([200, 200, 200, 422], $statuses);
        $this->assertSame('0.6298', $stock->fresh()->qty_available);
        $this->assertSame(3, Models\InventoryItemTransfer::query()->count());
        $this->assertSame(3, Models\InventoryTransaction::query()->count());
        $this->assertSame(['3.1234'], Models\InventoryItemTransfer::query()->pluck('qty')->unique()->all());
        $this->assertSame(['3.1234'], Models\InventoryTransaction::query()->pluck('qty')->unique()->all());
        $this->assertSame(0, Models\Inventory::query()->where('warehouse_id', $destination->id)->count());
    }

    public function test_parallel_reservations_preserve_fractional_batch_stock(): void
    {
        [$lab, $operator, $item, $source, $destination, $stock] = $this->fixture();
        $stock->update(['qty_available' => '0.5001']);
        $batch = Models\InventoryBatch::query()->create(['lab_id' => $lab->id, 'inventory_id' => $stock->id,
            'batch_number' => 'Reserved', 'qty_received' => '0.5000', 'qty_remaining' => '0.5000']);
        $batchBefore = $batch->fresh()->getAttributes();
        $operation = $this->operation($lab, $operator, $item, $source, $destination);
        $operation['rows'][0]['qty'] = '0.0001';
        $statuses = $this->compete([$lab], array_fill(0, 4, $operation));
        sort($statuses);
        $this->assertSame([200, 422, 422, 422], $statuses);
        $this->assertSame('0.5000', $stock->fresh()->qty_available);
        $this->assertSame($batchBefore, $batch->fresh()->getAttributes());
        $this->assertSame(1, Models\InventoryItemTransfer::query()->count());
        $this->assertSame('0.0001', Models\InventoryTransaction::query()->sole()->qty);
    }

    #[DataProvider('terminalStages')]
    public function test_competing_terminal_operations_move_stock_only_once(string $stage): void
    {
        [$lab, $operator, $item, $source, $destination, $stock] = $this->fixture();
        $operation = $this->operation($lab, $operator, $item, $source, $destination);
        $transfer = app(ManageInventoryTransfers::class)->create($lab->id, $operator, $operation['rows'][0]);
        $operation['stage'] = $stage;
        $operation['transfer'] = $transfer->id;
        $statuses = $this->compete([$lab], array_fill(0, 4, $operation));
        sort($statuses);
        $this->assertSame($stage === 'receive' ? [200, 422, 422, 422] : [200, 404, 404, 404], $statuses);
        $this->assertSame($stage === 'receive' ? '7.9999' : '10.0000', $stock->fresh()->qty_available);
        $stored = Models\InventoryItemTransfer::withTrashed()->findOrFail($transfer->id);
        $this->assertSame($stage === 'cancel', $stored->trashed());
        $this->assertSame($stage === 'receive', filled($stored->received_date));
        $this->assertSame($stage === 'receive' ? 3 : 2, Models\InventoryTransaction::query()->count());
        if ($stage === 'receive') {
            $this->assertSame('2.0001', Models\Inventory::query()->where('warehouse_id', $destination->id)->sole()->qty_available);
            $this->assertSame(['1.1233', '2.0001', '3.1234'], Models\InventoryTransaction::query()->orderBy('qty')->pluck('qty')->all());
        } else {
            $this->assertSame(0, Models\Inventory::query()->where('warehouse_id', $destination->id)->count());
        }
        $before = $this->snapshot();
        $this->assertSame($stage === 'receive' ? [422, 422] : [404, 404], $this->compete([$lab], [$operation, $operation]));
        $this->assertSame($before, $this->snapshot());
    }

    public static function terminalStages(): array
    {
        return ['receipt' => ['receive'], 'cancellation' => ['cancel']];
    }

    public function test_parallel_receipts_of_sibling_transfers_preserve_the_shared_destination_position(): void
    {
        [$lab, $operator, $item, $source, $destination, $stock] = $this->fixture();
        $first = $this->operation($lab, $operator, $item, $source, $destination, 'receive');
        $second = $first;
        $first['transfer'] = app(ManageInventoryTransfers::class)->create($lab->id, $operator, $first['rows'][0])->id;
        $second['transfer'] = app(ManageInventoryTransfers::class)->create($lab->id, $operator, $second['rows'][0])->id;
        $statuses = $this->compete([$lab], [$first, $first, $second, $second]);
        sort($statuses);
        $this->assertSame([200, 200, 422, 422], $statuses);
        $this->assertSame('5.9998', $stock->fresh()->qty_available);
        $this->assertSame('4.0002', Models\Inventory::query()->where('warehouse_id', $destination->id)->sole()->qty_available);
        $this->assertSame(2, Models\InventoryItemTransfer::query()->whereNotNull('received_date')->count());
        $this->assertSame(6, Models\InventoryTransaction::query()->count());
    }

    public function test_receipt_and_cancellation_have_one_terminal_winner(): void
    {
        [$lab, $operator, $item, $source, $destination, $stock] = $this->fixture();
        $operation = $this->operation($lab, $operator, $item, $source, $destination);
        $transfer = app(ManageInventoryTransfers::class)->create($lab->id, $operator, $operation['rows'][0]);
        $operation['transfer'] = $transfer->id;
        $receipt = ['stage' => 'receive'] + $operation;
        $cancel = ['stage' => 'cancel'] + $operation;
        $statuses = $this->compete([$lab], [$receipt, $cancel]);
        $stored = Models\InventoryItemTransfer::withTrashed()->findOrFail($transfer->id);
        if ($stored->trashed()) {
            $this->assertSame([404, 200], $statuses);
            $this->assertNull($stored->received_date);
            $this->assertSame('10.0000', $stock->fresh()->qty_available);
            $this->assertSame(0, Models\Inventory::query()->where('warehouse_id', $destination->id)->count());
            $this->assertSame(2, Models\InventoryTransaction::query()->count());
        } else {
            $this->assertSame([200, 422], $statuses);
            $this->assertNotNull($stored->received_date);
            $this->assertSame('7.9999', $stock->fresh()->qty_available);
            $this->assertSame('2.0001', Models\Inventory::query()->where('warehouse_id', $destination->id)->sole()->qty_available);
            $this->assertSame(3, Models\InventoryTransaction::query()->count());
        }
    }

    public function test_reversed_bulk_routes_complete_with_exact_balances(): void
    {
        [$lab, $operator, $item, $source, $destination, $stock] = $this->fixture();
        $third = Models\InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Third']);
        $fourth = Models\InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Fourth']);
        $secondStock = Models\Inventory::query()->create(['lab_id' => $lab->id, 'item_id' => $item->id, 'warehouse_id' => $third->id,
            'qty_available' => '10.0000', 'min_stock_level' => 0, 'reorder_point' => 0, 'status' => 'AVAILABLE']);
        $first = $this->operation($lab, $operator, $item, $source, $destination, 'bulk');
        $first['rows'][0]['qty'] = '0.1250';
        $secondRow = $first['rows'][0];
        $secondRow['source_id'] = $third->id;
        $secondRow['destination_id'] = $fourth->id;
        $first['rows'][] = $secondRow;
        $second = $first;
        $second['rows'] = array_reverse($first['rows']);
        $this->assertSame([200, 200], $this->compete([$lab], [$first, $second]));
        $this->assertSame('9.7500', $stock->fresh()->qty_available);
        $this->assertSame('9.7500', $secondStock->fresh()->qty_available);
        $this->assertSame(4, Models\InventoryItemTransfer::query()->count());
        $this->assertSame(4, Models\InventoryTransaction::query()->count());
    }

    public function test_independent_labs_can_create_the_same_missing_ledger_type(): void
    {
        [$lab, $operator, $item, $source, $destination, $stock] = $this->fixture();
        [$peer, $peerOperator, $peerItem, $peerSource, $peerDestination, $peerStock] = $this->fixture();
        $this->assertSame(0, Models\InventoryTransactionType::query()->count());
        $first = $this->operation($lab, $operator, $item, $source, $destination);
        $second = $this->operation($peer, $peerOperator, $peerItem, $peerSource, $peerDestination);
        $first['type_barrier'] = true;
        $second['type_barrier'] = true;
        $this->assertSame([200, 200], $this->compete([$lab, $peer], [$first, $second]));
        $this->assertSame('6.8766', $stock->fresh()->qty_available);
        $this->assertSame('6.8766', $peerStock->fresh()->qty_available);
        $this->assertSame(1, Models\InventoryTransactionType::query()->where('code', 'stock_out')->count());
        $this->assertSame([$lab->id, $peer->id], Models\InventoryItemTransfer::query()->orderBy('lab_id')->pluck('lab_id')->all());
        $this->assertSame([$lab->id, $peer->id], Models\InventoryTransaction::query()->orderBy('lab_id')->pluck('lab_id')->all());
    }

    #[DataProvider('revocations')]
    public function test_waiting_transfer_rechecks_authority_without_changing_inventory(string $stage, string $revocation): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        $operation = $this->operation($lab, $operator, $item, $source, $destination, $stage);
        if (in_array($stage, ['receive', 'cancel'], true)) {
            $operation['transfer'] = app(ManageInventoryTransfers::class)->create($lab->id, $operator, $operation['rows'][0])->id;
        }
        $before = $this->snapshot();
        $this->assertSame([403], $this->compete([$lab], [$operation], function () use ($revocation, $operator, $lab): void {
            if ($revocation === 'membership') {
                DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $operator->id)->delete();
            } elseif ($revocation === 'permission') {
                $operator->syncPermissions([]);
            } elseif ($revocation === 'laboratory') {
                $lab->delete();
            } else {
                $operator->forceFill($revocation === 'active' ? ['is_active' => false] : ['email_verified_at' => null])->save();
            }
        }));
        $this->assertSame($before, $this->snapshot());
    }

    public static function revocations(): array
    {
        $cases = [];
        foreach (['create', 'bulk', 'receive', 'cancel'] as $stage) {
            foreach (['membership', 'permission', 'active', 'verification', 'laboratory'] as $revocation) {
                $cases[$stage.'-'.$revocation] = [$stage, $revocation];
            }
        }

        return $cases;
    }

    #[DataProvider('allStages')]
    public function test_independent_connection_observes_inventory_only_after_real_outer_commit(string $stage): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        $operation = $this->operation($lab, $operator, $item, $source, $destination, $stage);
        if ($stage === 'adjust') {
            $operator->givePermissionTo(Models\Permission::findOrCreate('edit_inventory', 'web'));
            $operation = $this->adjustment($lab, $operator, $item, $source, $destination, 'add', '0.1250');
        } elseif ($stage === 'position-create') {
            $operator->givePermissionTo(Models\Permission::findOrCreate('add_inventory', 'web'));
            $operation['position'] = ['item_id' => $item->id, 'warehouse_id' => $destination->id,
                'qty_available' => '0.1250', 'min_stock_level' => '0.1250', 'reorder_point' => '0.5000'];
        } elseif ($stage === 'item-create') {
            $operator->givePermissionTo(Models\Permission::findOrCreate('add_iitems', 'web'));
            $operation = $this->catalogueCreation($lab, $operator, $item, $source, $destination);
        } elseif ($stage === 'item-update') {
            $operator->givePermissionTo(Models\Permission::findOrCreate('edit_iitems', 'web'));
            $operation['item_data'] = ['name' => 'Outer metadata edit', 'category_id' => $item->category_id, 'unit_id' => $item->unit_id];
        } elseif (in_array($stage, ['consume', 'reverse'], true)) {
            $operation = $this->consumptionOperation($lab, $operator, $item, $source, $destination, $stage);
        }
        if (in_array($stage, ['receive', 'cancel'], true)) {
            $operation['transfer'] = app(ManageInventoryTransfers::class)->create($lab->id, $operator, $operation['rows'][0])->id;
        }
        $observerName = 'transfer_observer_'.$this->schema;
        config(['database.connections.'.$observerName => DB::connection()->getConfig()]);
        $observer = DB::connection($observerName);
        try {
            $before = $this->snapshot($observer);
            DB::beginTransaction();
            $this->runOperation($operation);
            $this->assertSame($before, $this->snapshot($observer));
            $this->assertNotSame($before, $this->snapshot());
            DB::rollBack();
            $this->assertSame($before, $this->snapshot());
            $this->assertSame($before, $this->snapshot($observer));
            DB::beginTransaction();
            $this->runOperation($operation);
            $expected = $this->snapshot();
            $this->assertSame($before, $this->snapshot($observer));
            DB::commit();
            $this->assertSame($expected, $this->snapshot($observer));
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge($observerName);
            config(['database.connections.'.$observerName => null]);
        }
    }

    public static function allStages(): array
    {
        return ['creation' => ['create'], 'bulk' => ['bulk'], ...self::terminalStages(),
            'stock adjustment' => ['adjust'], 'opening position' => ['position-create'], 'catalogue item' => ['item-create'], 'catalogue edit' => ['item-update'],
            'consumption' => ['consume'], 'consumption reversal' => ['reverse']];
    }

    private function consumptionOperation(Models\VAPLab $lab, Models\User $operator, Models\InventoryItem $item,
        Models\InventoryItemWarehouse $source, Models\InventoryItemWarehouse $destination, string $stage = 'consume'): array
    {
        foreach (['add_reagent_consumption', 'delete_reagent_consumption'] as $permission) {
            $operator->givePermissionTo(Models\Permission::findOrCreate($permission, 'web'));
        }
        Models\ItemCategory::query()->whereKey($item->category_id)->update(['name' => 'Reagentes '.fake()->uuid()]);
        $operation = $this->operation($lab, $operator, $item, $source, $destination, $stage);
        $operation['consumption_data'] = ['warehouse_id' => $source->id, 'quantity_used' => '0.1250',
            'used_by' => 'Technician', 'date' => today()->toDateString()];
        if ($stage === 'reverse') {
            $operation['consumption_id'] = app(ConsumeInventoryReagent::class)->execute($lab->id, $operator, $item, $operation['consumption_data'])['consumption']->id;
        }

        return $operation;
    }

    private function fixture(): array
    {
        $lab = Models\VAPLab::factory()->create();
        $operator = Models\User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        foreach (['add_itransfers', 'edit_itransfers', 'delete_itransfers'] as $permission) {
            $operator->givePermissionTo(Models\Permission::findOrCreate($permission, 'web'));
        }
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $operator->id]);
        $category = Models\ItemCategory::query()->create(['name' => 'Transfer '.fake()->uuid()]);
        $unit = Models\InventoryUnit::query()->create(['code' => 'tr-'.fake()->numerify('######'), 'description' => 'Millilitres']);
        $item = Models\InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Transfer material', 'category_id' => $category->id, 'unit_id' => $unit->id]);
        $source = Models\InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Source']);
        $destination = Models\InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Destination']);
        $stock = Models\Inventory::query()->create(['lab_id' => $lab->id, 'item_id' => $item->id, 'warehouse_id' => $source->id,
            'qty_available' => '10.0000', 'min_stock_level' => 0, 'reorder_point' => 0, 'status' => 'AVAILABLE']);

        return [$lab, $operator, $item, $source, $destination, $stock];
    }

    private function operation(Models\VAPLab $lab, Models\User $operator, Models\InventoryItem $item, Models\InventoryItemWarehouse $source,
        Models\InventoryItemWarehouse $destination, string $stage = 'create'): array
    {
        return ['lab' => $lab->id, 'actor' => $operator->id, 'stage' => $stage, 'transfer' => null,
            'actual_qty' => '2.0001', 'date' => today()->toDateString(), 'notes' => 'Recorded evidence',
            'rows' => [['item_id' => $item->id, 'source_id' => $source->id, 'destination_id' => $destination->id,
                'qty' => '3.1234', 'sent_date' => today()->toDateString(), 'obs' => 'Recorded evidence']]];
    }

    private function runOperation(array $operation): void
    {
        $operator = Models\User::query()->findOrFail($operation['actor']);
        $transfer = $operation['transfer'] ? Models\InventoryItemTransfer::withTrashed()->findOrFail($operation['transfer']) : null;
        $action = app(ManageInventoryTransfers::class);
        match ($operation['stage']) {
            'warehouse-save' => app(SaveInventoryWarehouse::class)->execute($operation['lab'], $operator->id, $operation['warehouse_data'], $operation['warehouse_id']),
            'consume' => app(ConsumeInventoryReagent::class)->execute($operation['lab'], $operator,
                Models\InventoryItem::query()->findOrFail($operation['rows'][0]['item_id']), $operation['consumption_data']),
            'reverse' => app(ReverseInventoryReagentConsumption::class)->execute($operation['lab'], $operator->id, $operation['consumption_id']),
            'item-update' => app(UpdateInventoryItem::class)->execute($operation['lab'], $operator->id, $operation['rows'][0]['item_id'], $operation['item_data']),
            'item-create' => app(CreateInventoryItem::class)->execute($operation['lab'], $operator->id, $operation['item_data']),
            'adjust' => app(AdjustInventoryItemStock::class)->execute($operation['lab'], $operator,
                Models\InventoryItem::query()->findOrFail($operation['rows'][0]['item_id']), $operation['adjustment']),
            'position-create' => app(CreateInventoryPosition::class)->execute($operation['lab'], $operator->id, $operation['position']),
            'create' => $action->create($operation['lab'], $operator, $operation['rows'][0]),
            'bulk' => $action->createMany($operation['lab'], $operator, $operation['rows'], $operation['date'], null),
            'receive' => $action->receive($operation['lab'], $operator, $transfer, $operation['actual_qty'], $operation['date'], $operation['notes']),
            'cancel' => $action->cancel($operation['lab'], $operator, $transfer, $operation['notes']),
            'position-archive' => app(MutateInventoryPositions::class)->execute($operation['lab'], $operator->id, $operation['recordIds'], 'archive'),
            'warehouse-archive', 'warehouse-restore' => app(SetInventoryWarehousesArchived::class)->execute($operator->id, $operation['lab'], $operation['recordIds'], $operation['stage'] === 'warehouse-archive'),
        };
    }

    private function snapshot(?ConnectionInterface $connection = null): array
    {
        $connection ??= DB::connection();
        $snapshot = [];
        foreach ([new Models\InventoryItem, new Models\Inventory, new Models\InventoryItemTransfer, new Models\InventoryTransaction, new Models\InventoryBatch, new Models\InventoryTransactionType,
            new Models\ReagentConsumption, new Models\ReagentConsumptionReversal] as $model) {
            $snapshot[$model->getTable()] = $connection->table($model->getTable())->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
        }
        $snapshot['sequence_counters'] = $connection->table('sequence_counters')->orderBy('scope_hash')->get()->map(fn (object $row): array => (array) $row)->all();

        return $snapshot;
    }

    /** @param list<Models\VAPLab> $labs @param list<array<string,mixed>> $operations @return list<int> */
    private function compete(array $labs, array $operations, ?callable $beforeRelease = null): array
    {
        $connection = DB::connection();
        $barrier = sys_get_temp_dir().'/transfer-race-'.bin2hex(random_bytes(8));
        mkdir($barrier, 0700);
        $processes = [];
        $worker = <<<'PHP'
        require getcwd().'/vendor/autoload.php';
        $app = require getcwd().'/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $connection = Illuminate\Support\Facades\DB::connection();
        if (! $app->environment('testing') || $connection->getDatabaseName() !== 'lims_unleashed_test' || $connection->getConfig('search_path') !== $argv[1]) {
            throw new RuntimeException('Inventory transfer concurrency requires its dedicated test schema.');
        }
        $pid = $connection->selectOne('select pg_backend_pid() as pid')->pid;
        $connection->beforeExecuting(function (string $query) use ($argv, $pid): void {
            if (str_contains($query, 'from "labs"') && str_contains($query, 'for update')) {
                file_put_contents($argv[3].'/boundary-'.$argv[4], (string) $pid);
            }
        });
        $operation = json_decode($argv[2], true, flags: JSON_THROW_ON_ERROR);
        if ($operation['type_barrier'] ?? false) {
            App\Models\InventoryTransactionType::creating(function (App\Models\InventoryTransactionType $type) use ($argv, $operation): void {
                if ($type->code !== ($operation['type_code'] ?? 'stock_out')) {
                    return;
                }
                file_put_contents($argv[3].'/type-'.$argv[4], 'creating');
                $deadline = microtime(true) + 10;
                while (count(glob($argv[3].'/type-*') ?: []) < 2 && microtime(true) < $deadline) {
                    usleep(10000);
                }
                if (count(glob($argv[3].'/type-*') ?: []) !== 2) {
                    throw new RuntimeException('Both labs must attempt the absent shared type before insertion.');
                }
            });
        }
        $operator = App\Models\User::query()->findOrFail($operation['actor']);
        $transfer = $operation['transfer'] ? App\Models\InventoryItemTransfer::withTrashed()->findOrFail($operation['transfer']) : null;
        $action = $app->make(App\Actions\ManageInventoryTransfers::class);
        try {
            match ($operation['stage']) {
                'warehouse-save' => $app->make(App\Actions\SaveInventoryWarehouse::class)->execute($operation['lab'], $operator->id, $operation['warehouse_data'], $operation['warehouse_id']),
                'consume' => $app->make(App\Actions\ConsumeInventoryReagent::class)->execute($operation['lab'], $operator,
                    App\Models\InventoryItem::query()->findOrFail($operation['rows'][0]['item_id']), $operation['consumption_data']),
                'reverse' => $app->make(App\Actions\ReverseInventoryReagentConsumption::class)->execute($operation['lab'], $operator->id, $operation['consumption_id']),
                'item-update' => $app->make(App\Actions\UpdateInventoryItem::class)->execute($operation['lab'], $operator->id, $operation['rows'][0]['item_id'], $operation['item_data']),
                'item-create' => $app->make(App\Actions\CreateInventoryItem::class)->execute($operation['lab'], $operator->id, $operation['item_data']),
                'adjust' => $app->make(App\Actions\AdjustInventoryItemStock::class)->execute($operation['lab'], $operator,
                    App\Models\InventoryItem::query()->findOrFail($operation['rows'][0]['item_id']), $operation['adjustment']),
                'position-create' => $app->make(App\Actions\CreateInventoryPosition::class)->execute($operation['lab'], $operator->id, $operation['position']),
                'create' => $action->create($operation['lab'], $operator, $operation['rows'][0]),
                'bulk' => $action->createMany($operation['lab'], $operator, $operation['rows'], $operation['date'], null),
                'receive' => $action->receive($operation['lab'], $operator, $transfer, $operation['actual_qty'], $operation['date'], $operation['notes']),
                'cancel' => $action->cancel($operation['lab'], $operator, $transfer, $operation['notes']),
                'position-archive' => $app->make(App\Actions\MutateInventoryPositions::class)->execute($operation['lab'], $operator->id, $operation['recordIds'], 'archive'),
                'warehouse-archive', 'warehouse-restore' => $app->make(App\Actions\SetInventoryWarehousesArchived::class)->execute($operator->id, $operation['lab'], $operation['recordIds'], $operation['stage'] === 'warehouse-archive'),
            };
            $status = 200;
        } catch (Illuminate\Auth\Access\AuthorizationException) {
            $status = 403;
        } catch (Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $status = $exception->getStatusCode();
        } catch (Illuminate\Validation\ValidationException) {
            $status = 422;
        }
        echo json_encode(['status' => $status]);
        PHP;
        $environment = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DATABASE_URL' => '', 'DB_SCHEMA' => $this->schema,
            'DB_HOST' => $connection->getConfig('host'), 'DB_PORT' => (string) $connection->getConfig('port'), 'DB_DATABASE' => $connection->getDatabaseName(),
            'DB_USERNAME' => $connection->getConfig('username'), 'DB_PASSWORD' => $connection->getConfig('password') ?? '',
            'SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array', 'CACHE_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'database', 'MAIL_MAILER' => 'array'];
        try {
            DB::beginTransaction();
            DB::table((new Models\VAPLab)->getTable())->whereIn('id', collect($labs)->pluck('id'))->orderBy('id')->lockForUpdate()->get();
            foreach ($operations as $index => $operation) {
                $process = new Process([PHP_BINARY, '-r', $worker, $this->schema, json_encode($operation, JSON_THROW_ON_ERROR), $barrier, (string) $index], base_path(), $environment, timeout: 30);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 15;
            while (count(glob($barrier.'/boundary-*') ?: []) < count($operations) && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertCount(count($operations), glob($barrier.'/boundary-*') ?: [], collect($processes)->map(fn (Process $process): string => $process->getErrorOutput().$process->getOutput())->implode(' | '));
            foreach ($processes as $process) {
                $this->assertTrue($process->isRunning(), $process->getErrorOutput().$process->getOutput());
                $this->assertSame('', trim($process->getOutput()));
            }
            $workerIds = array_map(fn (string $path): int => (int) file_get_contents($path), glob($barrier.'/boundary-*') ?: []);
            $deadline = microtime(true) + 15;
            do {
                DB::select('select pg_stat_clear_snapshot()');
                $waiting = DB::table('pg_stat_activity')->whereIn('pid', $workerIds)->where('wait_event_type', 'Lock')->pluck('pid')->all();
                if (count($waiting) === count($operations)) {
                    break;
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            $this->assertCount(count($operations), $waiting, 'Every worker must actually wait on the held PostgreSQL access lock.');
            $beforeRelease?->__invoke();
            DB::commit();
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
                $results[] = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR)['status'];
            }
            if (collect($operations)->contains(fn (array $operation): bool => $operation['type_barrier'] ?? false)) {
                $this->assertCount(2, glob($barrier.'/type-*') ?: []);
            }

            return $results;
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            foreach (glob($barrier.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($barrier);
        }
    }
}
