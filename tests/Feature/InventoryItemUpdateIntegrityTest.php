<?php

namespace Tests\Feature;

use App\Actions\UpdateInventoryItem;
use App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use Tests\TestCase;

class InventoryItemUpdateIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('additionalReferences')]
    public function test_extended_metadata_is_editable_and_nullable_without_rewriting_stock(string $field, string $class, string $prop): void
    {
        [, $user, $item, , , , $payload] = $this->fixture();
        $lookup = $this->reference($class, 'Selected catalogue reference');
        $before = $this->snapshot();
        $payload[$field] = (string) $lookup->id;
        $payload['location'] = 'Sala técnica · bancada 4';
        $this->actingAs($user)->put(route('vap-inventory.items.update', $item), $payload)->assertRedirect();
        $this->assertSame($lookup->id, (int) $item->fresh()->$field);
        $this->assertSame($payload['location'], $item->fresh()->location);
        $after = $this->snapshot();
        unset($before['i_items'], $after['i_items']);
        $this->assertSame($before, $after);
        $payload[$field] = null;
        $this->put(route('vap-inventory.items.update', $item), $payload)->assertRedirect();
        $this->assertNull($item->fresh()->$field);
    }

    #[DataProvider('additionalReferences')]
    public function test_extended_edit_options_retain_only_the_current_archived_choice(string $field, string $class, string $prop): void
    {
        [, $user, $item] = $this->fixture();
        $current = $this->reference($class, 'Retained archived reference');
        $other = $this->reference($class, 'Other archived reference');
        DB::table('i_items')->where('id', $item->id)->update([$field => $current->id]);
        $current->delete();
        $other->delete();
        $this->actingAs($user)->get(route('vap-inventory.items.edit', $item))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('VAPInventory/Items/Edit')
                ->where('item.'.$field, $current->id)
                ->where($prop, fn ($rows): bool => collect($rows)->contains('id', $current->id) && ! collect($rows)->contains('id', $other->id)));
    }

    #[DataProvider('additionalReferences')]
    public function test_late_archival_of_a_selected_extended_reference_rolls_back_the_edit(string $field, string $class, string $prop): void
    {
        [, $user, $item, , , , $payload] = $this->fixture();
        $lookup = $this->reference($class, 'Selected catalogue reference');
        $payload[$field] = $lookup->id;
        $before = $this->snapshot();
        Models\InventoryItem::updated(fn () => DB::table($lookup->getTable())->where('id', $lookup->id)->update(['deleted_at' => now()]));
        $this->actingAs($user)->put(route('vap-inventory.items.update', $item), $payload)->assertStatus(409);
        $this->assertSame($before, $this->snapshot());
    }

    public function test_database_blocks_hard_deletion_of_an_omitted_retained_department_and_rolls_back_the_edit(): void
    {
        [$lab, $user, $item, , , , $payload] = $this->fixture();
        $department = Models\Department::factory()->create();
        DB::table('i_items')->where('id', $item->id)->update(['department_id' => $department->id]);
        $before = $this->snapshot();
        Models\InventoryItem::updated(fn () => DB::table($department->getTable())->where('id', $department->id)->delete());
        try {
            app(UpdateInventoryItem::class)->execute($lab->id, $user->id, $item->id, $payload);
            $this->fail('The referenced department must not be hard deleted.');
        } catch (QueryException $exception) {
            $this->assertSame('23503', $exception->getCode());
            $this->assertStringContainsString('i_items_department_id_foreign', $exception->getMessage());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public function test_late_archival_of_an_omitted_retained_department_rolls_back_the_edit(): void
    {
        [, $user, $item, , , , $payload] = $this->fixture();
        $department = Models\Department::factory()->create();
        DB::table('i_items')->where('id', $item->id)->update(['department_id' => $department->id]);
        $before = $this->snapshot();
        Models\InventoryItem::updated(fn () => DB::table($department->getTable())->where('id', $department->id)->update(['deleted_at' => now()]));
        $this->actingAs($user)->put(route('vap-inventory.items.update', $item), $payload)->assertStatus(409);
        $this->assertSame($before, $this->snapshot());
    }

    public static function additionalReferences(): array
    {
        return ['department' => ['department_id', Models\Department::class, 'departments'],
            'equipment class' => ['eq_cat_id', Models\EquipmentCategory::class, 'equipmentCategories'],
            'packaging type' => ['packaging_type_id', Models\PackagingCategory::class, 'packagingCategories']];
    }

    #[DataProvider('updateFaults')]
    public function test_update_fault_rolls_back_metadata_and_retained_evidence(string $fault): void
    {
        [$lab, $user, $item, $stock, $ledger, $media, $payload] = $this->fixture();
        $before = $this->snapshot();
        match ($fault) {
            'veto' => Models\InventoryItem::updating(fn (): bool => false),
            'name' => Models\InventoryItem::updating(function (Models\InventoryItem $item): void {
                $item->name = 'Changed intent';
            }),
            'raw_reagent' => Models\InventoryItem::updating(function (Models\InventoryItem $item): void {
                $item->is_reagent = true;
            }),
            default => Models\InventoryItem::updated(function (Models\InventoryItem $changed) use ($fault, $lab, $user, $item, $stock, $ledger, $media): void {
                match ($fault) {
                    'late_item' => DB::table('i_items')->where('id', $changed->id)->update(['brand' => 'Changed intent']),
                    'late_owner' => DB::table('i_items')->where('id', $changed->id)->update(['user_id' => null]),
                    'late_sequence' => DB::table('i_items')->where('id', $changed->id)->update(['seq' => 999]),
                    'stock' => DB::table('inventory')->where('id', $stock->id)->update(['qty_available' => '9.0000']),
                    'ledger' => DB::table('itransactions')->where('id', $ledger->id)->update(['notes' => 'Changed history']),
                    'media' => DB::table('media')->where('id', $media->id)->update(['model_id' => 0]),
                    'lab' => DB::table($lab->getTable())->where('id', $lab->id)->update(['name' => 'Changed lab']),
                    'actor' => DB::table('users')->where('id', $user->id)->update(['name' => 'Changed actor']),
                    'pivot' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->update(['can_manage_branding' => true]),
                    'membership' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->delete(),
                    'category' => DB::table('item_categories')->where('id', $item->category_id)->update(['code' => 'CHANGED']),
                    'unit' => DB::table('i_units')->where('id', $item->unit_id)->update(['description' => 'Changed unit']),
                    'warehouse' => DB::table('i_warehouses')->where('id', $stock->warehouse_id)->update(['deleted_at' => now()]),
                    'movement_type' => DB::table('itransaction_types')->where('id', $ledger->type_id)->update(['name' => 'Changed type']),
                    'counter' => DB::table('sequence_counters')->where('table_name', 'i_items')->update(['last_value' => 999]),
                    'extra_stock' => DB::table('inventory')->insert(['lab_id' => $lab->id, 'item_id' => $item->id, 'warehouse_id' => $stock->warehouse_id, 'qty_available' => 0, 'status' => 'AVAILABLE', 'deleted_at' => now()]),
                    'extra_media' => DB::table('media')->insert(array_replace((array) DB::table('media')->where('id', $media->id)->first(), ['id' => $media->id + 1000000, 'uuid' => fake()->uuid()])),
                };
            }),
        };
        $response = $this->actingAs($user)->put(route('vap-inventory.items.update', $item), $payload);
        $this->assertSame($before, $this->snapshot(), $fault.' must not commit partial or collateral changes.');
        $response->assertStatus(409);
    }

    public static function updateFaults(): array
    {
        $faults = ['veto', 'name', 'raw_reagent', 'late_item', 'late_owner', 'late_sequence', 'stock', 'ledger', 'media',
            'lab', 'actor', 'pivot', 'membership', 'category', 'unit', 'warehouse', 'movement_type', 'counter', 'extra_stock', 'extra_media'];

        return array_combine($faults, array_map(fn (string $fault): array => [$fault], $faults));
    }

    public function test_metadata_edit_preserves_issued_identity_history_and_unsubmitted_fields(): void
    {
        [$lab, $user, $item, , , , $payload] = $this->fixture();
        $before = $this->snapshot();
        $payload += ['code' => 'AGREED-CODE', 'internal_code' => 'AGREED-INTERNAL', 'standard_cost' => '12.3456',
            'reagent_open_date' => '2026-10-01', 'is_reagent' => false, 'reorder_qty' => null,
            'user_id' => Models\User::factory()->create()->id, 'lab_id' => Models\VAPLab::factory()->create()->id, 'seq' => 999];
        $this->actingAs($user)->put(route('vap-inventory.items.update', $item), $payload)->assertRedirect(route('vap-inventory.items.show', $item));
        $fresh = $item->fresh();
        $this->assertSame($payload['name'], $fresh->name);
        $this->assertSame('AGREED-CODE', $fresh->code);
        $this->assertSame('AGREED-INTERNAL', $fresh->internal_code);
        $this->assertSame($item->seq, $fresh->seq);
        $this->assertSame($lab->id, $fresh->lab_id);
        $this->assertSame($user->id, $fresh->user_id);
        $this->assertSame('Retained location', $fresh->location);
        $this->assertSame('12.3456', $fresh->standard_cost);
        $this->assertSame('0.00', $fresh->reorder_qty);
        $this->assertSame('2026-10-01', $fresh->reagent_open_date->toDateString());
        foreach (['inventory', 'itransactions', 'media', 'sequence_counters'] as $table) {
            $this->assertSame($before[$table], $this->snapshot()[$table]);
        }
    }

    public function test_metadata_edit_requires_catalogue_permission_not_stock_or_equipment_permission(): void
    {
        [, $user, $item, , , , $payload] = $this->fixture();
        $user->revokePermissionTo('edit_iitems');
        $user->givePermissionTo(Models\Permission::findOrCreate('edit_inventory', 'web'), Models\Permission::findOrCreate('edit_iequipments', 'web'));
        $before = $this->snapshot();
        $this->actingAs($user)->get(route('vap-inventory.items.edit', $item))->assertForbidden();
        $this->put(route('vap-inventory.items.update', $item), $payload)->assertForbidden();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_identical_metadata_replay_is_a_checked_no_op_even_at_document_order_limit(): void
    {
        [$lab, $user, $item, , , $media, $payload] = $this->fixture();
        DB::table('media')->where('id', $media->id)->update(['order_column' => 2147483647]);
        $payload['name'] = $item->name;
        $before = $this->snapshot();
        Models\InventoryItem::saving(fn (): bool => false);
        $this->actingAs($user)->put(route('vap-inventory.items.update', $item), $payload)->assertRedirect();
        $this->assertSame($before, $this->snapshot());
        $user->revokePermissionTo('edit_iitems');
        $this->put(route('vap-inventory.items.update', $item), $payload)->assertForbidden();
    }

    public function test_document_order_limit_does_not_block_metadata_but_rejects_append_atomically(): void
    {
        Storage::fake('local');
        [, $user, $item, , , $media, $payload] = $this->fixture();
        DB::table('media')->where('id', $media->id)->update(['order_column' => 2147483647]);
        $this->actingAs($user)->put(route('vap-inventory.items.update', $item), $payload)->assertRedirect();
        $payload['name'] = 'Must roll back';
        $payload['documents'] = [$this->document()];
        $before = $this->snapshot();
        $this->post(route('vap-inventory.items.update', $item), $payload + ['_method' => 'put'])->assertStatus(409);
        $this->assertSame($before, $this->snapshot());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_append_uses_only_document_collection_order_and_accepts_last_available_slot(): void
    {
        Storage::fake('local');
        [, $user, $item, , , $media, $payload] = $this->fixture();
        DB::table('media')->where('id', $media->id)->update(['order_column' => 2147483646]);
        Models\InventoryItemDocumentMedia::factory()->create(['model_id' => $item->id, 'collection_name' => 'other', 'order_column' => 2147483647]);
        $payload['documents'] = [$this->document()];
        $this->actingAs($user)->post(route('vap-inventory.items.update', $item), $payload + ['_method' => 'put'])->assertRedirect();
        $new = $item->fresh()->getMedia('documents')->firstWhere('id', '!=', $media->id);
        $this->assertSame(2147483647, $new->order_column);
    }

    #[DataProvider('linkedEvidence')]
    public function test_linked_connector_and_uncertainty_evidence_cannot_be_changed_or_inserted(string $class, string $fault): void
    {
        [$lab, $user, $item, , , , $payload] = $this->fixture();
        $record = $class === Models\IntegrationConnector::class
            ? Models\IntegrationConnector::factory()->create(['lab_id' => $lab->id, 'inventory_item_id' => $item->id])
            : Models\UncertaintySource::query()->create(['lab_id' => $lab->id, 'inventory_item_id' => $item->id, 'title' => 'Retained uncertainty', 'source_type' => 'equipment']);
        $before = $this->snapshot();
        Models\InventoryItem::updated(function () use ($record, $fault): void {
            $query = DB::table($record->getTable());
            if ($fault === 'move') {
                $query->where('id', $record->id)->update(['inventory_item_id' => null]);
            } else {
                $attributes = (array) $query->where('id', $record->id)->first();
                unset($attributes['id']);
                if ($record instanceof Models\IntegrationConnector) {
                    $attributes['uuid'] = fake()->uuid();
                    $attributes['key'] = 'injected-'.fake()->uuid();
                }
                DB::table($record->getTable())->insert($attributes);
            }
        });
        $this->actingAs($user)->put(route('vap-inventory.items.update', $item), $payload)->assertStatus(409);
        $this->assertSame($before, $this->snapshot());
    }

    public static function linkedEvidence(): array
    {
        return ['connector moved' => [Models\IntegrationConnector::class, 'move'], 'connector inserted' => [Models\IntegrationConnector::class, 'insert'],
            'uncertainty moved' => [Models\UncertaintySource::class, 'move'], 'uncertainty inserted' => [Models\UncertaintySource::class, 'insert']];
    }

    public function test_retained_null_category_rejects_guessed_permissions_and_issued_scope_changes(): void
    {
        [$lab, $user] = $this->fixture();
        $item = Models\InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Unclassified retained item']);
        $seq = $item->seq;
        $before = $this->snapshot();
        $this->actingAs($user)->put(route('vap-inventory.items.update', $item), ['name' => 'Corrected metadata', 'category_id' => null])->assertNotFound();
        $this->assertSame($before, $this->snapshot());
        $this->assertSame('Unclassified retained item', $item->fresh()->name);
        $this->assertNull($item->fresh()->category_id);
        $this->assertSame($seq, $item->fresh()->seq);
        $category = Models\ItemCategory::query()->create(['name' => 'Not an ordinary correction']);
        $before = $this->snapshot();
        $this->put(route('vap-inventory.items.update', $item), ['name' => 'Forbidden scope change', 'category_id' => $category->id])->assertSessionHasErrors('category_id');
        $this->assertSame($before, $this->snapshot());
    }

    public function test_retained_null_unit_can_be_filled_even_with_historical_stock(): void
    {
        [, $user, $item, $stock, , , $payload] = $this->fixture();
        DB::table('i_items')->where('id', $item->id)->update(['unit_id' => null]);
        $stock->delete();
        $this->actingAs($user)->put(route('vap-inventory.items.update', $item), $payload)->assertRedirect();
        $this->assertSame($payload['unit_id'], $item->fresh()->unit_id);
    }

    public function test_new_lookup_archived_by_an_update_hook_cannot_commit(): void
    {
        [, $user, $item, , , , $payload] = $this->fixture();
        $type = Models\InventoryItemType::query()->create(['name' => 'New selected type']);
        $payload['type_id'] = $type->id;
        $before = $this->snapshot();
        Models\InventoryItem::updated(fn () => DB::table($type->getTable())->where('id', $type->id)->update(['deleted_at' => now()]));
        $this->actingAs($user)->put(route('vap-inventory.items.update', $item), $payload)->assertStatus(409);
        $this->assertSame($before, $this->snapshot());
    }

    #[DataProvider('invalidMetadata')]
    public function test_direct_metadata_action_validates_before_writing(string $field, mixed $value): void
    {
        [$lab, $user, $item, , , , $payload] = $this->fixture();
        $payload[$field] = $value;
        $before = $this->snapshot();
        try {
            app(UpdateInventoryItem::class)->execute($lab->id, $user->id, $item->id, $payload);
            $this->fail('Invalid direct metadata payload must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public static function invalidMetadata(): array
    {
        return ['negative cost' => ['standard_cost', '-0.0001'], 'cost precision' => ['standard_cost', '1.00001'],
            'pack precision' => ['packed_depth', '1.001'], 'stock injection' => ['warehouses', [['id' => 1, 'qty_available' => '900']]],
            'blank unit with history' => ['unit_id', null], 'location too long' => ['location', str_repeat('x', 256)],
            'missing department' => ['department_id', 2147483647], 'missing equipment class' => ['eq_cat_id', 2147483647],
            'missing packaging type' => ['packaging_type_id', 2147483647]];
    }

    public function test_unit_can_change_before_any_stock_exists(): void
    {
        [$lab, $user, , , , , $payload] = $this->fixture();
        $item = Models\InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Unused item', 'category_id' => $payload['category_id'], 'unit_id' => $payload['unit_id']]);
        $unit = Models\InventoryUnit::query()->create(['code' => 'unused-'.fake()->uuid()]);
        $payload['unit_id'] = $unit->id;
        $this->actingAs($user)->put(route('vap-inventory.items.update', $item), $payload)->assertRedirect();
        $this->assertSame($unit->id, $item->fresh()->unit_id);
    }

    public function test_related_nonconformity_evidence_cannot_be_relinked_during_metadata_edit(): void
    {
        [$lab, $user, $item, , , , $payload] = $this->fixture();
        $nc = Models\VAPNonConformity::query()->create(['lab_id' => $lab->id, 'equipment_id' => $item->id,
            'reported_by' => $user->name, 'reported_by_id' => $user->id,
            'nc_number' => 'TEST-'.fake()->uuid(), 'title' => 'Retained evidence', 'description' => 'Retained description',
            'status' => 'open', 'severity' => 'minor', 'category' => 'equipment', 'reported_at' => now()]);
        $before = $this->snapshot();
        Models\InventoryItem::updated(fn () => DB::table($nc->getTable())->where('id', $nc->id)->update(['equipment_id' => null]));
        $this->actingAs($user)->put(route('vap-inventory.items.update', $item), $payload)->assertStatus(409);
        $this->assertSame($before, $this->snapshot());
    }

    #[DataProvider('lockedIdentity')]
    public function test_issued_category_and_historical_stock_unit_cannot_change(string $field, bool $archivedStock): void
    {
        [, $user, $item, $stock, , , $payload] = $this->fixture();
        if ($archivedStock) {
            $stock->delete();
        }
        $lookup = $field === 'category_id' ? Models\ItemCategory::query()->create(['name' => 'Other category'])
            : Models\InventoryUnit::query()->create(['code' => 'other-'.fake()->uuid()]);
        $payload[$field] = $lookup->id;
        $before = $this->snapshot();
        $this->actingAs($user)->put(route('vap-inventory.items.update', $item), $payload)->assertSessionHasErrors($field);
        $this->assertSame($before, $this->snapshot());
    }

    public static function lockedIdentity(): array
    {
        return ['category' => ['category_id', false], 'active stock unit' => ['unit_id', false], 'archived stock unit' => ['unit_id', true]];
    }

    #[DataProvider('lookupFields')]
    public function test_unchanged_archived_lookup_is_retained_but_new_archived_selection_is_rejected(string $field, string $class): void
    {
        [, $user, $item, , , , $payload] = $this->fixture();
        $lookup = isset($item->$field) ? $class::query()->findOrFail($item->$field) : $this->reference($class, 'Retained lookup');
        DB::table('i_items')->where('id', $item->id)->update([$field => $lookup->id]);
        $item->refresh();
        $lookup->delete();
        $payload[$field] = $lookup->id;
        $this->actingAs($user)->put(route('vap-inventory.items.update', $item), $payload)->assertRedirect();
        $this->assertSame($lookup->id, $item->fresh()->$field);
        if (in_array($field, ['category_id', 'unit_id'], true)) {
            return;
        }
        $other = $this->reference($class, 'Other archived lookup');
        $other->delete();
        $payload[$field] = $other->id;
        $before = $this->snapshot();
        $this->put(route('vap-inventory.items.update', $item), $payload)->assertSessionHasErrors($field);
        $this->assertSame($before, $this->snapshot());
    }

    public static function lookupFields(): array
    {
        return ['category' => ['category_id', Models\ItemCategory::class], 'unit' => ['unit_id', Models\InventoryUnit::class],
            'type' => ['type_id', Models\InventoryItemType::class], 'supplier' => ['supplier_id', Models\InventoryItemSupplier::class],
            'status' => ['status_id', Models\ItemStatus::class],
            'department' => ['department_id', Models\Department::class], 'equipment class' => ['eq_cat_id', Models\EquipmentCategory::class],
            'packaging type' => ['packaging_type_id', Models\PackagingCategory::class]];
    }

    public function test_edit_props_include_retained_archived_choices_and_identity_locks(): void
    {
        [, $user, $item] = $this->fixture();
        $item->category->delete();
        $item->unit->delete();
        $this->actingAs($user)->get(route('vap-inventory.items.edit', $item))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('VAPInventory/Items/Edit')
                ->where('identityLocks.category', true)->where('identityLocks.unit', true)
                ->where('categories', fn ($rows): bool => collect($rows)->contains('id', $item->category_id))
                ->where('units', fn ($rows): bool => collect($rows)->contains('id', $item->unit_id)));
    }

    #[DataProvider('documentFaults')]
    public function test_failed_edit_documents_remove_only_new_files(string $fault): void
    {
        Storage::fake('local');
        Storage::fake('public');
        [, $user, $item, , , $media, $payload] = $this->fixture();
        $retainedPath = $media->getPathRelativeToRoot();
        Storage::disk('public')->put($retainedPath, 'Retained public evidence');
        $before = $this->snapshot();
        $payload['documents'] = [$this->document(), $this->document('second.pdf')];
        if ($fault === 'veto') {
            Models\InventoryItemDocumentMedia::creating(fn (): bool => false);
        } else {
            Event::listen(MediaHasBeenAddedEvent::class, function (MediaHasBeenAddedEvent $event) use ($fault, $item): void {
                match ($fault) {
                    'throw' => throw new \RuntimeException('Injected document failure'),
                    'row' => DB::table('media')->where('id', $event->media->id)->update(['name' => 'Changed document']),
                    'bytes' => Storage::disk('local')->put($event->media->getPathRelativeToRoot(), 'Changed bytes'),
                    'late_root' => DB::table('i_items')->where('id', $item->id)->update(['name' => 'Changed after upload']),
                };
            });
        }
        $this->actingAs($user)->post(route('vap-inventory.items.update', $item), $payload + ['_method' => 'put'])->assertStatus(409);
        $this->assertSame($before, $this->snapshot());
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame([$retainedPath], Storage::disk('public')->allFiles());
        $this->assertSame('Retained public evidence', Storage::disk('public')->get($retainedPath));
    }

    public static function documentFaults(): array
    {
        return ['veto' => ['veto'], 'throw' => ['throw'], 'row' => ['row'], 'bytes' => ['bytes'], 'late root' => ['late_root']];
    }

    public function test_edit_appends_private_documents_without_reordering_or_moving_retained_media(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        [, $user, $item, , , $media, $payload] = $this->fixture();
        $path = $media->getPathRelativeToRoot();
        Storage::disk('public')->put($path, 'Retained evidence');
        $before = (array) DB::table('media')->where('id', $media->id)->first();
        $payload['documents'] = [$this->document()];
        $this->actingAs($user)->post(route('vap-inventory.items.update', $item), $payload + ['_method' => 'put'])->assertRedirect();
        $this->assertSame($before, (array) DB::table('media')->where('id', $media->id)->first());
        $new = $item->fresh()->getMedia('documents')->firstWhere('id', '!=', $media->id);
        $this->assertSame('local', $new->disk);
        $this->assertSame(8, $new->order_column);
        Storage::disk('local')->assertExists($new->getPathRelativeToRoot());
        $this->assertSame([$path], Storage::disk('public')->allFiles());
        $this->get($new->getUrl())->assertOk()->assertDownload($new->file_name);
    }

    public function test_ancestor_rollback_removes_only_appended_documents_and_reverts_metadata(): void
    {
        Storage::fake('local');
        [$lab, $user, $item, , , , $payload] = $this->fixture();
        Storage::disk('local')->put('retained/evidence.pdf', 'Retained evidence');
        $before = $this->snapshot();
        DB::beginTransaction();
        try {
            app(UpdateInventoryItem::class)->execute($lab->id, $user->id, $item->id, $payload + ['documents' => [$this->document()]]);
            $this->assertCount(2, Storage::disk('local')->allFiles());
        } finally {
            DB::rollBack();
        }
        $this->assertSame($before, $this->snapshot());
        $this->assertSame(['retained/evidence.pdf'], Storage::disk('local')->allFiles());
    }

    private function fixture(): array
    {
        $lab = Models\VAPLab::factory()->create();
        $user = Models\User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        $user->givePermissionTo(Models\Permission::findOrCreate('edit_iitems', 'web'), Models\Permission::findOrCreate('view_iitems', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $category = Models\ItemCategory::query()->create(['name' => 'Update category '.fake()->uuid(), 'code' => 'UI-'.fake()->uuid()]);
        $unit = Models\InventoryUnit::query()->create(['code' => 'ui-'.fake()->uuid(), 'description' => 'Millilitres']);
        $warehouse = Models\InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Retained warehouse']);
        $item = Models\InventoryItem::query()->create(['lab_id' => $lab->id, 'user_id' => $user->id, 'name' => 'Retained item',
            'category_id' => $category->id, 'unit_id' => $unit->id, 'location' => 'Retained location', 'is_reagent' => false]);
        $stock = Models\Inventory::query()->create(['lab_id' => $lab->id, 'item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'qty_available' => '2.1250']);
        $type = Models\InventoryTransactionType::query()->create(['code' => 'stock_in', 'name' => 'Retained opening type']);
        $ledger = Models\InventoryTransaction::query()->create(['lab_id' => $lab->id, 'item_id' => $item->id, 'inventory_id' => $stock->id,
            'warehouse_id' => $warehouse->id, 'user_id' => $user->id, 'type_id' => $type->id, 'qty' => '2.1250']);
        $media = Models\InventoryItemDocumentMedia::factory()->create(['model_id' => $item->id, 'order_column' => 7]);
        $this->withSession(['active_lab_id' => $lab->id]);
        Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);

        return [$lab, $user, $item, $stock, $ledger, $media, ['name' => 'Updated metadata', 'category_id' => $category->id, 'unit_id' => $unit->id]];
    }

    private function document(string $name = 'certificate.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    }

    /** @param class-string<Model> $class */
    private function reference(string $class, string $name): Model
    {
        if ($class === Models\Department::class) {
            return Models\Department::factory()->create(['name' => $name]);
        }
        $attributes = ['name' => $name.' '.fake()->uuid()];
        if ($class === Models\EquipmentCategory::class) {
            $attributes['code'] = 'EC-'.fake()->uuid();
        }

        return $class::query()->create($attributes);
    }

    private function snapshot(): array
    {
        $snapshot = [];
        foreach ([(new Models\VAPLab)->getTable(), 'users', 'lab_user', 'item_categories', 'i_units', 'i_warehouses', 'i_items',
            'inventory', 'itransactions', 'itransaction_types', 'i_types', 'media', 'sequence_counters', 'integration_connectors', 'uncertainty_sources', 'v_non_conformities', 'departments', 'equipment_categories', 'packaging_categories'] as $table) {
            $snapshot[$table] = DB::table($table)->orderBy($table === 'sequence_counters' ? 'scope_hash' : 'id')->get()->map(fn (object $row): array => (array) $row)->all();
        }

        return $snapshot;
    }
}
