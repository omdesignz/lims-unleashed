<?php

namespace Tests\Feature;

use App\Actions\CreateInventoryItem;
use App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use Tests\TestCase;

class InventoryItemCreationIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('additionalReferences')]
    public function test_creation_preserves_extended_catalogue_metadata(string $field, string $class, string $prop): void
    {
        [$lab, $user, , $payload] = $this->fixture(false);
        $lookup = $this->reference($class, 'Selected catalogue reference');
        $archived = $this->reference($class, 'Unselectable archived reference');
        $archived->delete();
        $this->actingAs($user)->get(route('vap-inventory.items.create'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('VAPInventory/Items/Create')
                ->where($prop, fn ($rows): bool => collect($rows)->contains('id', $lookup->id) && ! collect($rows)->contains('id', $archived->id)));
        $payload[$field] = (string) $lookup->id;
        $payload['location'] = 'Sala técnica · bancada 4';
        $this->post(route('vap-inventory.items.store'), $payload)->assertRedirect();
        $item = Models\InventoryItem::forLaboratory($lab->id)->where('name', $payload['name'])->firstOrFail();
        $this->assertSame($lookup->id, (int) $item->$field);
        $this->assertSame($payload['location'], $item->location);
    }

    #[DataProvider('additionalReferences')]
    public function test_creation_rejects_late_extended_reference_archival(string $field, string $class, string $prop): void
    {
        [, $user, , $payload] = $this->fixture();
        $lookup = $this->reference($class, 'Selected catalogue reference');
        $payload[$field] = $lookup->id;
        $before = $this->snapshot();
        Models\InventoryTransaction::created(fn () => DB::table($lookup->getTable())->where('id', $lookup->id)->update(['deleted_at' => now()]));
        $this->actingAs($user)->post(route('vap-inventory.items.store'), $payload)->assertStatus(409);
        $this->assertSame($before, $this->snapshot());
    }

    public static function additionalReferences(): array
    {
        return ['department' => ['department_id', Models\Department::class, 'departments'],
            'equipment class' => ['eq_cat_id', Models\EquipmentCategory::class, 'equipmentCategories'],
            'packaging type' => ['packaging_type_id', Models\PackagingCategory::class, 'packagingCategories']];
    }

    #[DataProvider('creationFaults')]
    public function test_creation_fault_rolls_back_the_complete_item_graph(string $fault): void
    {
        [$lab, $user, $warehouse, $payload] = $this->fixture();
        $before = $this->snapshot();
        match ($fault) {
            'item_veto' => Models\InventoryItem::creating(fn (): bool => false),
            'stock_veto' => Models\Inventory::creating(fn (): bool => false),
            'ledger_veto' => Models\InventoryTransaction::creating(fn (): bool => false),
            'type_veto' => Models\InventoryTransactionType::creating(fn (): bool => false),
            'item_name' => Models\InventoryItem::creating(function (Models\InventoryItem $item): void {
                $item->name = 'Changed intent';
            }),
            'item_sequence' => Models\InventoryItem::creating(function (Models\InventoryItem $item): void {
                $item->seq = 999;
            }),
            'item_internal_code' => Models\InventoryItem::creating(function (Models\InventoryItem $item): void {
                $item->internal_code = 'Changed-code';
            }),
            'item_reagent' => Models\InventoryItem::creating(function (Models\InventoryItem $item): void {
                $item->is_reagent = true;
            }),
            'stock_quantity' => Models\Inventory::creating(function (Models\Inventory $stock): void {
                $stock->qty_available = '9.0000';
            }),
            'ledger_quantity' => Models\InventoryTransaction::creating(function (Models\InventoryTransaction $ledger): void {
                $ledger->qty = '9.0000';
            }),
            default => Models\InventoryTransaction::created(function (Models\InventoryTransaction $ledger) use ($fault, $lab, $user, $warehouse, $payload): void {
                match ($fault) {
                    'late_item' => DB::table('i_items')->where('id', $ledger->item_id)->update(['name' => 'Late change']),
                    'late_stock' => DB::table('inventory')->where('id', $ledger->inventory_id)->update(['qty_available' => '9.0000']),
                    'late_ledger' => DB::table('itransactions')->where('id', $ledger->id)->update(['notes' => 'Late change']),
                    'late_lab' => DB::table($lab->getTable())->where('id', $lab->id)->update(['name' => 'Late change']),
                    'late_actor' => DB::table('users')->where('id', $user->id)->update(['name' => 'Late change']),
                    'late_membership' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->delete(),
                    'late_pivot' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->update(['can_manage_branding' => true]),
                    'late_warehouse' => DB::table($warehouse->getTable())->where('id', $warehouse->id)->update(['deleted_at' => now()]),
                    'late_category' => DB::table('item_categories')->where('id', $payload['category_id'])->update(['code' => 'CHANGED']),
                    'late_type' => DB::table('itransaction_types')->where('id', $ledger->type_id)->update(['name' => 'Late change']),
                    'extra_position' => DB::table('inventory')->insert(['lab_id' => $lab->id, 'item_id' => $ledger->item_id, 'warehouse_id' => $warehouse->id, 'qty_available' => '0.0000', 'status' => 'AVAILABLE', 'deleted_at' => now()]),
                    'extra_ledger' => DB::table('itransactions')->insert(['lab_id' => $lab->id, 'inventory_id' => $ledger->inventory_id, 'item_id' => $ledger->item_id, 'warehouse_id' => $warehouse->id, 'user_id' => $user->id, 'type_id' => $ledger->type_id, 'qty' => '1.0000']),
                    'sequence_counter' => DB::table('sequence_counters')->where('table_name', 'i_items')->update(['last_value' => 999]),
                };
            }),
        };
        $response = $this->actingAs($user)->post(route('vap-inventory.items.store'), $payload);
        $this->assertSame($before, $this->snapshot(), $fault.' must leave no committed item, stock, ledger or collateral change.');
        $response->assertStatus(409);
    }

    public static function creationFaults(): array
    {
        $faults = ['item_veto', 'stock_veto', 'ledger_veto', 'type_veto', 'item_name', 'item_sequence', 'item_internal_code', 'item_reagent',
            'stock_quantity', 'ledger_quantity', 'late_item', 'late_stock', 'late_ledger', 'late_lab', 'late_actor', 'late_membership',
            'late_pivot', 'late_warehouse', 'late_category', 'late_type', 'extra_position', 'extra_ledger', 'sequence_counter'];

        return array_combine($faults, array_map(fn (string $fault): array => [$fault], $faults));
    }

    public function test_add_only_operator_can_create_fractional_and_zero_opening_positions(): void
    {
        [$lab, $user, , $payload] = $this->fixture(false);
        $second = Models\InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Zero opening warehouse']);
        $payload['warehouses'][] = ['id' => $second->id, 'qty_available' => '0', 'min_stock_level' => '0.0001', 'reorder_point' => '0.1250'];
        $payload['user_id'] = Models\User::factory()->create()->id;
        $this->actingAs($user)->post(route('vap-inventory.items.store'), $payload)->assertRedirect(route('vap-inventory.items.create'));
        $item = Models\InventoryItem::query()->where('name', $payload['name'])->sole();
        $this->assertSame($lab->id, $item->lab_id);
        $this->assertSame($user->id, $item->user_id);
        $this->assertSame(2, $item->inventory()->count());
        $ledger = $item->transactions()->sole();
        $this->assertSame('0.1250', $ledger->qty);
        $this->assertSame('Existências iniciais', $ledger->reason);
        $this->assertSame('stock_in', $ledger->type->code);
        $this->assertSame('0.0000', $item->inventory()->where('warehouse_id', $second->id)->sole()->qty_available);
        $this->assertFalse($user->can('add_inventory'));
    }

    public function test_catalogue_creation_requires_its_own_permission(): void
    {
        [, $user, , $payload] = $this->fixture(false);
        $user->revokePermissionTo('add_iitems');
        $user->givePermissionTo(Models\Permission::findOrCreate('add_inventory', 'web'));
        $before = $this->snapshot();
        $this->actingAs($user)->get(route('vap-inventory.items.create'))->assertForbidden();
        $this->post(route('vap-inventory.items.store'), $payload)->assertForbidden();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_new_documents_are_private_and_downloads_require_local_view_access(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        [$lab, $user, , $payload] = $this->fixture();
        $payload['documents'] = [$this->document()];
        $this->actingAs($user)->post(route('vap-inventory.items.store'), $payload)->assertRedirect(route('vap-inventory.items.index'));
        $item = Models\InventoryItem::query()->where('name', $payload['name'])->sole();
        $media = $item->getMedia('documents')->sole();
        $this->assertSame('local', $media->disk);
        Storage::disk('local')->assertExists($media->getPathRelativeToRoot());
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame(route('vap-inventory.items.attachments.download-single', ['model_id' => $media->id]), $media->getUrl());
        $this->get($media->getUrl())->assertOk()->assertDownload($media->file_name);
        $peer = Models\VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $peer->id, 'user_id' => $user->id]);
        $this->withSession(['active_lab_id' => $peer->id])->get($media->getUrl())->assertNotFound();
        $viewer = Models\User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $viewer->id]);
        $this->actingAs($viewer)->withSession(['active_lab_id' => $lab->id])->get($media->getUrl())->assertForbidden();
    }

    #[DataProvider('documentFaults')]
    public function test_document_failure_cleans_only_new_files_and_preserves_database_evidence(string $fault): void
    {
        Storage::fake('local');
        Storage::fake('public');
        [, $user, , $payload] = $this->fixture();
        Storage::disk('local')->put('retained/evidence.pdf', 'Retained evidence');
        $payload['documents'] = [$this->document(), $this->document('second.pdf')];
        $before = $this->snapshot();
        match ($fault) {
            'veto' => Models\InventoryItemDocumentMedia::creating(fn (): bool => false),
            'owner' => Models\InventoryItemDocumentMedia::creating(function (Models\InventoryItemDocumentMedia $media): void {
                $media->model_id = 0;
            }),
            'filename' => Models\InventoryItemDocumentMedia::creating(function (Models\InventoryItemDocumentMedia $media): void {
                $media->file_name = 'changed.pdf';
            }),
            default => Event::listen(MediaHasBeenAddedEvent::class, function (MediaHasBeenAddedEvent $event) use ($fault): void {
                if ($fault === 'throw') {
                    throw new \RuntimeException('Injected document failure');
                }
                if ($fault === 'row') {
                    DB::table('media')->where('id', $event->media->id)->update(['name' => 'Changed document']);
                }
                if ($fault === 'bytes') {
                    Storage::disk('local')->put($event->media->getPathRelativeToRoot(), 'Changed bytes');
                }
            }),
        };
        $this->actingAs($user)->post(route('vap-inventory.items.store'), $payload)->assertStatus(409);
        $this->assertSame($before, $this->snapshot());
        $this->assertSame(['retained/evidence.pdf'], Storage::disk('local')->allFiles());
        $this->assertSame('Retained evidence', Storage::disk('local')->get('retained/evidence.pdf'));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public static function documentFaults(): array
    {
        return ['veto' => ['veto'], 'owner' => ['owner'], 'filename' => ['filename'], 'throw' => ['throw'], 'row' => ['row'], 'bytes' => ['bytes']];
    }

    public function test_outer_transaction_rollback_removes_created_documents(): void
    {
        Storage::fake('local');
        [$lab, $user, , $payload] = $this->fixture();
        $payload['documents'] = [$this->document()];
        $before = $this->snapshot();
        DB::beginTransaction();
        try {
            app(CreateInventoryItem::class)->execute($lab->id, $user->id, $payload);
            $this->assertCount(1, Storage::disk('local')->allFiles());
        } finally {
            DB::rollBack();
        }
        $this->assertSame($before, $this->snapshot());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    #[DataProvider('retainedFaults')]
    public function test_retained_stock_history_and_document_rows_cannot_change_during_creation(string $fault): void
    {
        [$lab, $user, $warehouse, $payload] = $this->fixture();
        $item = Models\InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Retained item',
            'category_id' => $payload['category_id'], 'unit_id' => $payload['unit_id']]);
        $stock = Models\Inventory::query()->create(['lab_id' => $lab->id, 'item_id' => $item->id,
            'warehouse_id' => $warehouse->id, 'qty_available' => '2.0000']);
        $type = Models\InventoryTransactionType::query()->create(['code' => 'stock_in', 'name' => 'Retained opening type']);
        $ledger = Models\InventoryTransaction::query()->create(['lab_id' => $lab->id, 'item_id' => $item->id,
            'inventory_id' => $stock->id, 'warehouse_id' => $warehouse->id, 'user_id' => $user->id, 'type_id' => $type->id, 'qty' => '2.0000']);
        $media = Models\InventoryItemDocumentMedia::factory()->create(['model_id' => $item->id]);
        $before = $this->snapshot();
        Models\InventoryItem::created(function () use ($fault, $stock, $ledger, $media, $type): void {
            match ($fault) {
                'stock' => DB::table('inventory')->where('id', $stock->id)->update(['qty_available' => '9.0000']),
                'ledger' => DB::table('itransactions')->where('id', $ledger->id)->update(['qty' => '9.0000']),
                'media' => DB::table('media')->where('id', $media->id)->update(['model_id' => 0]),
                'type' => DB::table('itransaction_types')->where('id', $type->id)->update(['name' => 'Changed opening type']),
            };
        });
        $this->actingAs($user)->post(route('vap-inventory.items.store'), $payload)->assertStatus(409);
        $this->assertSame($before, $this->snapshot());
    }

    public static function retainedFaults(): array
    {
        return ['stock' => ['stock'], 'ledger' => ['ledger'], 'media' => ['media'], 'type' => ['type']];
    }

    public function test_retained_public_media_keeps_its_original_path(): void
    {
        Storage::fake('public');
        [$lab, $user, , $payload] = $this->fixture();
        $item = Models\InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Retained item',
            'category_id' => $payload['category_id'], 'unit_id' => $payload['unit_id']]);
        $media = Models\InventoryItemDocumentMedia::factory()->create(['model_id' => $item->id]);
        $path = $media->id.'/retained.pdf';
        Storage::disk('public')->put($path, 'Retained public bytes');
        $originalUrl = $media->getUrl();
        $this->actingAs($user)->post(route('vap-inventory.items.store'), $payload)->assertRedirect();
        $this->assertSame($path, $media->getPathRelativeToRoot());
        $this->assertSame($originalUrl, $media->fresh()->getUrl());
        $this->assertSame('Retained public bytes', Storage::disk('public')->get($path));
    }

    public function test_blank_optional_numeric_metadata_uses_defaults_without_rounding_submitted_values(): void
    {
        [, $user, , $payload] = $this->fixture();
        $payload += ['reorder_qty' => null, 'packed_depth' => '1.25', 'standard_cost' => '12.3456',
            'metrological_uncertainty_value' => '0.0001', 'reagent_open_date' => '2026-10-01', 'is_reagent' => true];
        $this->actingAs($user)->post(route('vap-inventory.items.store'), $payload)->assertRedirect();
        $item = Models\InventoryItem::query()->where('name', $payload['name'])->sole();
        $this->assertSame('0.00', $item->reorder_qty);
        $this->assertSame('1.25', $item->packed_depth);
        $this->assertSame('12.3456', $item->standard_cost);
        $this->assertSame('0.0001', $item->metrological_uncertainty_value);
        $this->assertSame('2026-10-01', $item->reagent_open_date->toDateString());
        $this->assertTrue((bool) $item->getRawOriginal('is_reagent'));
    }

    #[DataProvider('invalidCreationData')]
    public function test_direct_action_rejects_invalid_payloads_without_writing(string $field, mixed $value): void
    {
        [$lab, $user, , $payload] = $this->fixture();
        Arr::set($payload, $field, $value);
        $before = $this->snapshot();
        try {
            app(CreateInventoryItem::class)->execute($lab->id, $user->id, $payload);
            $this->fail('Invalid item payload must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public static function invalidCreationData(): array
    {
        return ['negative stock' => ['warehouses.0.qty_available', '-0.0001'], 'precision' => ['warehouses.0.qty_available', '0.00001'],
            'range' => ['warehouses.0.qty_available', '100000000000000'], 'unknown child' => ['warehouses.0', ['id' => 1, 'qty_available' => '0', 'lab_id' => 1]],
            'metadata precision' => ['packed_depth', '1.001'], 'cost precision' => ['standard_cost', '1.00001'],
            'location too long' => ['location', str_repeat('x', 256)], 'missing department' => ['department_id', 2147483647],
            'missing equipment class' => ['eq_cat_id', 2147483647], 'missing packaging type' => ['packaging_type_id', 2147483647]];
    }

    #[DataProvider('archivedReferences')]
    public function test_new_item_cannot_use_an_archived_lookup(string $field, string $class): void
    {
        [, $user, , $payload] = $this->fixture();
        $lookup = isset($payload[$field]) ? $class::query()->findOrFail($payload[$field])
            : $this->reference($class, 'Archived lookup');
        $lookup->delete();
        $payload[$field] = $lookup->id;
        $before = $this->snapshot();
        $this->actingAs($user)->post(route('vap-inventory.items.store'), $payload)->assertSessionHasErrors($field);
        $this->assertSame($before, $this->snapshot());
    }

    public static function archivedReferences(): array
    {
        return ['category' => ['category_id', Models\ItemCategory::class], 'unit' => ['unit_id', Models\InventoryUnit::class],
            'type' => ['type_id', Models\InventoryItemType::class], 'supplier' => ['supplier_id', Models\InventoryItemSupplier::class],
            'status' => ['status_id', Models\ItemStatus::class],
            'department' => ['department_id', Models\Department::class], 'equipment class' => ['eq_cat_id', Models\EquipmentCategory::class],
            'packaging type' => ['packaging_type_id', Models\PackagingCategory::class]];
    }

    /** @return array{Models\VAPLab,Models\User,Models\InventoryItemWarehouse,array<string,mixed>} */
    private function fixture(bool $admin = true): array
    {
        $lab = Models\VAPLab::factory()->create();
        $user = Models\User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        if ($admin) {
            $user->assignRole(Models\Role::findOrCreate('admin', 'web'));
        } else {
            $user->givePermissionTo(Models\Permission::findOrCreate('add_iitems', 'web'));
        }
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $category = Models\ItemCategory::query()->create(['name' => 'Creation '.fake()->uuid(), 'code' => 'CI-'.fake()->uuid()]);
        $unit = Models\InventoryUnit::query()->create(['code' => 'ci-'.fake()->uuid(), 'description' => 'Millilitres']);
        $warehouse = Models\InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Opening warehouse']);
        $this->withSession(['active_lab_id' => $lab->id]);
        Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);

        return [$lab, $user, $warehouse, ['name' => 'Canonical item '.fake()->uuid(), 'category_id' => $category->id, 'unit_id' => $unit->id,
            'warehouses' => [['id' => $warehouse->id, 'qty_available' => '0.1250', 'min_stock_level' => '0.0001', 'reorder_point' => '0.0125']]]];
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

    /** @return array<string,array<mixed>> */
    private function snapshot(): array
    {
        $snapshot = [];
        foreach ([(new Models\VAPLab)->getTable(), 'users', 'lab_user', 'item_categories', 'i_units', 'i_warehouses', 'i_items', 'inventory', 'itransactions', 'itransaction_types', 'media', 'sequence_counters', 'departments', 'equipment_categories', 'packaging_categories'] as $table) {
            $snapshot[$table] = DB::table($table)->orderBy($table === 'sequence_counters' ? 'scope_hash' : 'id')->get()->map(fn (object $row): array => (array) $row)->all();
        }

        return $snapshot;
    }
}
