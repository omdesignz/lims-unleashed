<?php

namespace Tests\Feature;

use App\Actions\SetInventoryItemsArchived;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemDocumentMedia;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionType;
use App\Models\ISOActivityLog;
use App\Models\ItemCategory;
use App\Models\Permission;
use App\Models\ReagentConsumption;
use App\Models\ReagentConsumptionReversal;
use App\Models\User;
use App\Models\VAPLab;
use App\Support\ExportHubQuery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InventoryItemArchiveIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('kinds')]
    public function test_canonical_archive_rejects_a_delete_veto_instead_of_reporting_success(string $kind, string $prefix): void
    {
        [, $item] = $this->fixture($kind, $prefix);
        $before = $item->refresh()->getRawOriginal();
        Event::listen('eloquent.deleting: '.InventoryItem::class, fn (InventoryItem $record): bool => false);
        try {
            $this->deleteJson(route('vap-inventory.items.destroy', $item))->assertConflict();
            $this->assertSame($before, $item->fresh()->getRawOriginal());
        } finally {
            Event::forget('eloquent.deleting: '.InventoryItem::class);
        }
    }

    #[DataProvider('getPaths')]
    public function test_get_lifecycle_paths_never_mutate_items(string $kind, string $prefix, string $operation): void
    {
        [, $item] = $this->fixture($kind, $prefix);
        if ($operation === 'restore') {
            $item->delete();
        }
        $before = $item->refresh()->getRawOriginal();
        $this->get(route($prefix.'.'.$operation, ['recordIds' => [$item->id]]))->assertStatus(405);
        $this->assertSame($before, InventoryItem::withTrashed()->findOrFail($item->id)->getRawOriginal());
    }

    /** @return array<string,array{string,string}> */
    public static function kinds(): array
    {
        return ['material' => ['material', 'iitems'], 'equipment' => ['equipment', 'iequipments']];
    }

    /** @return array<string,array{string,string,string}> */
    public static function getPaths(): array
    {
        return ['material archive' => ['material', 'iitems', 'destroy'], 'material restore' => ['material', 'iitems', 'restore'],
            'equipment archive' => ['equipment', 'iequipments', 'destroy'], 'equipment restore' => ['equipment', 'iequipments', 'restore']];
    }

    #[DataProvider('kinds')]
    public function test_archive_restore_replay_retains_stock_documents_identity_and_one_audit_per_transition(string $kind, string $prefix): void
    {
        [$user, $item] = $this->fixture($kind, $prefix);
        $warehouse = InventoryItemWarehouse::query()->create(['lab_id' => $item->lab_id, 'name' => 'Retained stock']);
        Inventory::query()->create(['lab_id' => $item->lab_id, 'item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'qty_available' => '2.1250']);
        Storage::fake('public');
        $media = InventoryItemDocumentMedia::factory()->create(['model_id' => $item->id]);
        $path = $media->getPathRelativeToRoot();
        Storage::disk('public')->put($path, 'Retained evidence bytes');
        $before = $this->snapshot();
        $item->category->delete();
        foreach ([true, false] as $archived) {
            $this->{$archived ? 'delete' : 'patch'}(route('vap-inventory.items.'.($archived ? 'destroy' : 'restore'), $item),
                ['recordIds' => [2147483647]])->assertRedirect();
            $stored = InventoryItem::withTrashed()->findOrFail($item->id);
            $this->assertSame($archived, $stored->trashed());
            $root = $stored->getRawOriginal();
            $original = $before['i_items'][0];
            unset($root['deleted_at'], $root['updated_at'], $original['deleted_at'], $original['updated_at']);
            $this->assertSame($original, $root);
            foreach (['inventory', 'media', 'sequence_counters'] as $table) {
                $this->assertSame($before[$table], $this->snapshot()[$table]);
            }
            $this->assertSame('Retained evidence bytes', Storage::disk('public')->get($path));
            $audit = ISOActivityLog::withoutGlobalScopes()->where('log_name', 'inventory_item')->latest('id')->firstOrFail();
            $this->assertSame($item->lab_id, $audit->properties->get('lab_id'));
            $this->assertSame($user->id, $audit->causer_id);
            $this->assertSame($archived ? 'archived' : 'restored', $audit->event);
            $replay = $this->snapshot();
            $this->{$archived ? 'delete' : 'patch'}(route($prefix.'.'.($archived ? 'destroy' : 'restore')), ['recordIds' => [(string) $item->id]])->assertRedirect();
            $this->assertSame($replay, $this->snapshot());
        }
        $this->assertSame(2, ISOActivityLog::withoutGlobalScopes()->where('log_name', 'inventory_item')->count());
    }

    #[DataProvider('transitions')]
    public function test_later_veto_rolls_back_every_selected_item_and_audit(bool $archived): void
    {
        [, $item] = $this->fixture('material', 'iitems');
        $second = InventoryItem::query()->create(['lab_id' => $item->lab_id, 'category_id' => $item->category_id, 'name' => 'Second item']);
        if (! $archived) {
            $item->delete();
            $second->delete();
        }
        $before = $this->snapshot();
        InventoryItem::{$archived ? 'deleting' : 'restoring'}(fn (InventoryItem $record): ?bool => $record->id === $second->id ? false : null);
        $this->{$archived ? 'delete' : 'patch'}(route('iitems.'.($archived ? 'destroy' : 'restore')), ['recordIds' => [$second->id, $item->id]])->assertConflict();
        $this->assertSame($before, $this->snapshot());
    }

    #[DataProvider('transitions')]
    public function test_foreign_missing_and_wrong_kind_batches_are_atomic(bool $archived): void
    {
        [, $item] = $this->fixture('material', 'iitems');
        $category = ItemCategory::query()->create(['name' => fake()->uuid(), 'inventory_type' => 'equipment']);
        $wrongKind = InventoryItem::query()->create(['lab_id' => $item->lab_id, 'category_id' => $category->id, 'name' => 'Equipment']);
        $foreign = InventoryItem::query()->create(['lab_id' => VAPLab::factory()->create()->id, 'category_id' => $item->category_id, 'name' => 'Peer material']);
        if (! $archived) {
            $item->delete();
            $wrongKind->delete();
            $foreign->delete();
        }
        $before = $this->snapshot();
        foreach ([$wrongKind->id, $foreign->id, 2147483647] as $id) {
            $this->{$archived ? 'delete' : 'patch'}(route('iitems.'.($archived ? 'destroy' : 'restore')), ['recordIds' => [$item->id, $id]])->assertNotFound();
        }
        $this->assertSame($before, $this->snapshot());
    }

    #[DataProvider('malformedBatches')]
    public function test_http_and_internal_calls_reject_malformed_batches(array $ids): void
    {
        [$user, $item] = $this->fixture('material', 'iitems');
        $before = $this->snapshot();
        $this->json('DELETE', route('iitems.destroy'), ['recordIds' => $ids], [], JSON_PRESERVE_ZERO_FRACTION)->assertUnprocessable();
        try {
            app(SetInventoryItemsArchived::class)->execute($user->id, $item->lab_id, $ids, true);
            $this->fail('Internal lifecycle calls must validate their batch.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public static function malformedBatches(): array
    {
        $cases = [[], [0], [-1], [true], [1.0], ['1e0'], ['+1'], ['01'], ['1.0'], ['bad'], ['9223372036854775808'], [[1]], [null], [1, '1'], ['key' => 1], array_fill(0, 101, 1)];

        return array_map(fn (array $ids): array => [$ids], $cases);
    }

    #[DataProvider('faults')]
    public function test_late_faults_roll_back_roots_stock_media_audits_and_authority(bool $archived, string $fault): void
    {
        [$user, $item] = $this->fixture('material', 'iitems');
        $warehouse = InventoryItemWarehouse::query()->create(['lab_id' => $item->lab_id, 'name' => 'Stock']);
        $stock = Inventory::query()->create(['lab_id' => $item->lab_id, 'item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'qty_available' => '2.1250']);
        $media = InventoryItemDocumentMedia::factory()->create(['model_id' => $item->id]);
        $old = activity('inventory_item')->causedBy($user)->performedOn($item)->withProperties(['lab_id' => $item->lab_id])->log('Retained history');
        if (! $archived) {
            $item->delete();
        }
        $before = $this->snapshot();
        ISOActivityLog::creating(function (ISOActivityLog $audit) use ($fault, $item, $user, $stock, $media, $old): ?bool {
            if ($audit->log_name !== 'inventory_item') {
                return null;
            }
            match ($fault) {
                'veto' => null,
                'audit' => $audit->description = 'Changed evidence',
                'root' => DB::table('i_items')->where('id', $item->id)->update(['name' => 'Changed identity']),
                'stock' => DB::table('inventory')->where('id', $stock->id)->update(['qty_available' => '9.0000']),
                'media' => DB::table('media')->where('id', $media->id)->update(['model_id' => 0]),
                'history' => DB::table('activity_log')->where('id', $old->id)->update(['subject_id' => 2147483647]),
                'category' => DB::table('item_categories')->where('id', $item->category_id)->update(['name' => 'Changed classification']),
                'membership' => DB::table('lab_user')->where('lab_id', $item->lab_id)->where('user_id', $user->id)->delete(),
                'permission' => DB::table('model_has_permissions')->where('model_id', $user->id)->where('model_type', $user->getMorphClass())->delete(),
                'actor' => DB::table('users')->where('id', $user->id)->update(['name' => 'Changed operator']),
                'lab' => DB::table('labs')->where('id', $item->lab_id)->update(['name' => 'Changed lab']),
            };

            return $fault === 'veto' ? false : null;
        });
        $this->{$archived ? 'delete' : 'patch'}(route('iitems.'.($archived ? 'destroy' : 'restore')), ['recordIds' => [$item->id]])
            ->assertStatus(in_array($fault, ['membership', 'permission'], true) ? 403 : 409);
        $this->assertSame($before, $this->snapshot());
    }

    public static function faults(): array
    {
        $cases = [];
        foreach ([true, false] as $archived) {
            foreach (['veto', 'audit', 'root', 'stock', 'media', 'history', 'category', 'membership', 'permission', 'actor', 'lab'] as $fault) {
                $cases[($archived ? 'archive ' : 'restore ').$fault] = [$archived, $fault];
            }
        }

        return $cases;
    }

    #[DataProvider('transitions')]
    public function test_canonical_lifecycle_requires_exact_kind_permission_even_on_replay(bool $archived): void
    {
        [$user, $item] = $this->fixture('equipment', 'iequipments');
        if (! $archived) {
            $item->delete();
        }
        $user->revokePermissionTo(($archived ? 'delete' : 'restore').'_iequipments');
        $user->givePermissionTo(Permission::findOrCreate(($archived ? 'delete' : 'restore').'_iitems', 'web'));
        $before = $this->snapshot();
        $this->{$archived ? 'delete' : 'patch'}(route('vap-inventory.items.'.($archived ? 'destroy' : 'restore'), $item))->assertForbidden();
        $this->assertSame($before, $this->snapshot());
    }

    public static function transitions(): array
    {
        return ['archive' => [true], 'restore' => [false]];
    }

    public function test_archived_catalogue_is_lab_kind_scoped_and_exposes_only_restore_actions(): void
    {
        [$user, $item] = $this->fixture('material', 'iitems');
        $user->givePermissionTo(Permission::findOrCreate('view_iitems', 'web'), Permission::findOrCreate('edit_iitems', 'web'));
        $equipmentCategory = ItemCategory::query()->create(['name' => fake()->uuid(), 'inventory_type' => 'equipment']);
        $equipment = InventoryItem::query()->create(['lab_id' => $item->lab_id, 'category_id' => $equipmentCategory->id, 'name' => 'Hidden equipment']);
        $peer = InventoryItem::query()->create(['lab_id' => VAPLab::factory()->create()->id, 'category_id' => $item->category_id, 'name' => 'Hidden peer']);
        foreach ([$item, $equipment, $peer] as $record) {
            $record->delete();
        }
        $item->category->delete();
        $this->get(route('vap-inventory.items.index'))->assertOk()->assertInertia(fn (Assert $page) => $page->has('items.data', 0));
        $this->get(route('vap-inventory.items.index', ['archive_state' => 'archived']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('items.data', 1)->where('items.data.0.id', $item->id)
                ->where('items.data.0.is_archived', true)->where('items.data.0.can_edit', false)
                ->where('items.data.0.can_delete', false)->where('items.data.0.can_restore', true)
                ->where('stats.total_items', 0));
        $this->getJson(route('vap-inventory.items.index', ['archive_state' => 'all']))->assertUnprocessable();
        $this->get(route('vap-inventory.items.show', $item))->assertNotFound();
        $archivedUrl = route('vap-inventory.items.index', ['archive_state' => 'archived']);
        $this->from($archivedUrl)->patch(route('vap-inventory.items.restore', $item))->assertRedirect($archivedUrl);
    }

    public function test_catalogue_activity_is_private_to_lab_and_exact_view_kind_including_archived_roots(): void
    {
        [$user, $item] = $this->fixture('material', 'iitems');
        $user->givePermissionTo(Permission::findOrCreate('view_iitems', 'web'), Permission::findOrCreate('view_activity_log', 'web'));
        $category = ItemCategory::query()->create(['name' => fake()->uuid(), 'inventory_type' => 'equipment']);
        $equipment = InventoryItem::query()->create(['lab_id' => $item->lab_id, 'category_id' => $category->id, 'name' => 'Private equipment']);
        $peerLab = VAPLab::factory()->create();
        $peer = InventoryItem::query()->create(['lab_id' => $peerLab->id, 'category_id' => $item->category_id, 'name' => 'Private peer']);
        DB::table('lab_user')->where('lab_id', $item->lab_id)->where('user_id', $user->id)->update(['can_view_network' => true]);
        $audits = [];
        foreach ([$item, $equipment, $peer] as $record) {
            $audits[] = activity('inventory_item')->causedBy($user)->performedOn($record)->withProperties(['lab_id' => $record->lab_id])->log('Private catalogue evidence')->id;
            $record->delete();
        }
        $item->category->delete();
        foreach ($audits as $index => $id) {
            $this->getJson(route('systemactivity.show', $id))->assertStatus($index === 0 ? 200 : 404);
        }
        request()->attributes->set('proposal_laboratory_id', $item->lab_id);
        request()->setUserResolver(fn () => $user);
        $this->assertSame([$audits[0]], ISOActivityLog::query()->whereIn('id', $audits)->pluck('id')->all());
        $this->assertSame([$audits[0]], app(ExportHubQuery::class)->forDataset('activity_log', [])->whereIn('activity_log.id', $audits)->pluck('activity_log.id')->all());
        request()->attributes->remove('proposal_laboratory_id');
        $this->assertSame([], ISOActivityLog::query()->whereIn('id', $audits)->pluck('id')->all());
    }

    public function test_canonical_lifecycle_redirects_only_to_the_local_catalogue_index(): void
    {
        [, $item] = $this->fixture('material', 'iitems');
        foreach (['https://external.example/catalogue', route('vap-inventory.items.show', $item), route('vap-inventory.items.index').'/unexpected'] as $referer) {
            $this->from($referer)->delete(route('vap-inventory.items.destroy', $item))->assertRedirect(route('vap-inventory.items.index'));
            $this->from($referer)->patch(route('vap-inventory.items.restore', $item))->assertRedirect(route('vap-inventory.items.index'));
        }
    }

    #[DataProvider('transitions')]
    public function test_reversal_linked_only_through_retained_movement_cannot_be_changed(bool $archived): void
    {
        [$user, $item] = $this->fixture('material', 'iitems');
        $other = InventoryItem::query()->create(['lab_id' => $item->lab_id, 'category_id' => $item->category_id, 'name' => 'Separate consumption item']);
        $warehouse = InventoryItemWarehouse::query()->create(['lab_id' => $item->lab_id, 'name' => 'Stock']);
        $stock = Inventory::query()->create(['lab_id' => $item->lab_id, 'item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'qty_available' => '2.1250']);
        $type = InventoryTransactionType::query()->create(['code' => 'archive-evidence', 'name' => 'Evidence']);
        $movement = InventoryTransaction::query()->create(['lab_id' => $item->lab_id, 'item_id' => $item->id, 'inventory_id' => $stock->id,
            'warehouse_id' => $warehouse->id, 'user_id' => $user->id, 'type_id' => $type->id, 'qty' => '0.1250']);
        $consumption = ReagentConsumption::query()->create(['lab_id' => $item->lab_id, 'warehouse_id' => $warehouse->id,
            'reagent_id' => $other->id, 'reagent_name' => $other->name, 'quantity_used' => '0.1250', 'used_by' => 'Technician',
            'used_at' => now(), 'date' => today(), 'user_id' => $user->id]);
        $reversal = ReagentConsumptionReversal::factory()->forConsumption($consumption, $movement)->create(['user_id' => $user->id]);
        if (! $archived) {
            $item->delete();
        }
        $before = $this->snapshot();
        $retained = $reversal->refresh()->getRawOriginal();
        ISOActivityLog::creating(fn () => DB::table($reversal->getTable())->where('id', $reversal->id)->update(['reversed_at' => now()->subDay()]));
        $this->{$archived ? 'delete' : 'patch'}(route('iitems.'.($archived ? 'destroy' : 'restore')), ['recordIds' => [$item->id]])->assertConflict();
        $this->assertSame($before, $this->snapshot());
        $this->assertSame($retained, $reversal->fresh()->getRawOriginal());
    }

    /** @return array<string,array<int,array<string,mixed>>> */
    private function snapshot(): array
    {
        $snapshot = [];
        foreach (['i_items', 'item_categories', 'inventory', 'media', 'activity_log', 'sequence_counters', 'labs', 'users', 'lab_user', 'model_has_permissions'] as $table) {
            $order = match ($table) {
                'model_has_permissions' => ['permission_id', 'model_id'],
                'lab_user' => ['lab_id', 'user_id'],
                'sequence_counters' => ['scope_hash'],
                default => ['id'],
            };
            $query = DB::table($table);
            foreach ($order as $column) {
                $query->orderBy($column);
            }
            $snapshot[$table] = $query->get()->map(fn (object $row): array => (array) $row)->all();
        }

        return $snapshot;
    }

    /** @return array{User,InventoryItem} */
    private function fixture(string $kind, string $prefix): array
    {
        $lab = VAPLab::factory()->create();
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        foreach (['delete', 'restore'] as $ability) {
            $user->givePermissionTo(Permission::findOrCreate($ability.'_'.$prefix, 'web'));
        }
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $category = ItemCategory::query()->create(['name' => fake()->uuid(), 'inventory_type' => $kind]);
        $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'category_id' => $category->id, 'name' => 'Retained catalogue item']);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        return [$user, $item];
    }
}
