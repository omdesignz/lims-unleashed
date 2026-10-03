<?php

namespace Tests\Feature;

use App\Actions\CreateInventoryItem;
use App\Actions\UpdateInventoryItem;
use App\Exports\InventoryItemsExport;
use App\Models\InventoryItem;
use App\Models\InventoryItemDocumentMedia;
use App\Models\InventoryUnit;
use App\Models\ItemCategory;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InventoryCataloguePermissionTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('typePermissions')]
    public function test_canonical_creation_uses_only_the_selected_category_permission(string $type, string $grant, bool $allowed): void
    {
        [$lab, $user, $category, $payload] = $this->fixture($type, $grant, ['add']);
        $response = $this->post(route('vap-inventory.items.store'), $payload);
        if ($allowed) {
            $response->assertRedirect(route('vap-inventory.items.create'));
            $this->assertDatabaseHas('i_items', ['lab_id' => $lab->id, 'category_id' => $category->id, 'name' => $payload['name']]);
        } else {
            $response->assertForbidden();
            $this->assertDatabaseMissing('i_items', ['name' => $payload['name']]);
        }
        $this->assertSame($user->id, auth()->id());
    }

    #[DataProvider('typePermissions')]
    public function test_canonical_update_and_editor_use_the_issued_category(string $type, string $grant, bool $allowed): void
    {
        [$lab, , $category, $payload] = $this->fixture($type, $grant, ['edit']);
        $item = InventoryItem::query()->create($payload + ['lab_id' => $lab->id]);
        $response = $this->get(route('vap-inventory.items.edit', $item));
        $update = $this->put(route('vap-inventory.items.update', $item), ['category_id' => $category->id, 'name' => 'Changed metadata', 'inventory_type' => $grant]);
        if ($allowed) {
            $response->assertOk()->assertInertia(fn (Assert $page) => $page->where('categories.0.inventory_type', $type));
            $update->assertRedirect(route('vap-inventory.items.edit', $item));
            $this->assertSame('Changed metadata', $item->fresh()->name);
            $other = ItemCategory::query()->create(['name' => fake()->uuid(), 'inventory_type' => $type === 'equipment' ? 'material' : 'equipment']);
            $this->put(route('vap-inventory.items.update', $item), ['category_id' => $other->id])->assertSessionHasErrors('category_id');
            $this->assertSame($category->id, $item->fresh()->category_id);
        } else {
            $response->assertForbidden();
            $update->assertForbidden();
            $this->assertSame($payload['name'], $item->fresh()->name);
        }
    }

    /** @return array<string,array{string,string,bool}> */
    public static function typePermissions(): array
    {
        $cases = [];
        foreach (['material', 'equipment'] as $type) {
            foreach (['material', 'equipment', 'both', 'none'] as $grant) {
                $cases[$type.'-'.$grant] = [$type, $grant, $grant === $type || $grant === 'both'];
            }
        }

        return $cases;
    }

    #[DataProvider('grants')]
    public function test_catalogue_list_filters_stats_and_detail_are_type_scoped(string $grant): void
    {
        [$lab, , $material, $payload] = $this->fixture('material', $grant, ['view', 'add']);
        $equipment = ItemCategory::query()->create(['name' => 'Renamed instrument family '.fake()->uuid(), 'inventory_type' => 'equipment']);
        $items = ['material' => InventoryItem::query()->create($payload + ['lab_id' => $lab->id]),
            'equipment' => InventoryItem::query()->create(['name' => 'Instrument', 'lab_id' => $lab->id, 'category_id' => $equipment->id])];
        $response = $this->get(route('vap-inventory.items.index'));
        $create = $this->get(route('vap-inventory.items.create'));
        if ($grant === 'none') {
            $response->assertForbidden();
            $create->assertForbidden();
        } else {
            $count = $grant === 'both' ? 2 : 1;
            $response->assertOk()->assertInertia(fn (Assert $page) => $page->has('items.data', $count)
                ->has('categories', $count)->where('stats.total_items', $count)
                ->where('stats.equipment_count', $grant === 'material' ? 0 : 1)
                ->where('canCreate', true)->where('canExport', false)
                ->where('items.data.0.can_edit', false)->where('items.data.0.can_delete', false));
            $create->assertOk()->assertInertia(fn (Assert $page) => $page->has('categories', $count));
        }
        foreach ($items as $type => $item) {
            $show = $this->get(route('vap-inventory.items.show', $item));
            if ($grant === $type || $grant === 'both') {
                $show->assertOk()->assertInertia(fn (Assert $page) => $page->where('canEdit', false));
            } else {
                $show->assertForbidden();
                if ($grant !== 'none') {
                    $this->get(route('vap-inventory.items.index', ['category_id' => $item->category_id]))
                        ->assertOk()->assertInertia(fn (Assert $page) => $page->has('items.data', 0));
                }
            }
        }
        $material->delete();
        if ($grant === 'material' || $grant === 'both') {
            $this->get(route('vap-inventory.items.show', $items['material']))->assertOk()
                ->assertInertia(fn (Assert $page) => $page->where('item.category.inventory_type', 'material'));
            $this->get(route('vap-inventory.items.index'))->assertOk()
                ->assertInertia(fn (Assert $page) => $page->where('stats.total_items', $grant === 'both' ? 2 : 1));
        }
    }

    /** @return array<string,array{string}> */
    public static function grants(): array
    {
        return ['material' => ['material'], 'equipment' => ['equipment'], 'both' => ['both'], 'none' => ['none']];
    }

    #[DataProvider('operationTypes')]
    public function test_final_checks_do_not_fall_back_to_the_other_kind_permission(string $type, bool $creating): void
    {
        [$lab, $user, , $payload] = $this->fixture($type, 'both', ['add', 'edit']);
        $item = InventoryItem::query()->create($payload + ['lab_id' => $lab->id]);
        $permission = ($creating ? 'add_' : 'edit_').($type === 'equipment' ? 'iequipments' : 'iitems');
        $before = $item->fresh()->getAttributes();
        InventoryItem::saved(fn () => $user->revokePermissionTo($permission));
        try {
            try {
                if ($creating) {
                    app(CreateInventoryItem::class)->execute($lab->id, $user->id, array_replace($payload, ['name' => 'Rejected late create']));
                } else {
                    app(UpdateInventoryItem::class)->execute($lab->id, $user->id, $item->id, ['category_id' => $item->category_id, 'name' => 'Rejected late edit']);
                }
                $this->fail('The selected-kind permission must remain authorized at commit.');
            } catch (AuthorizationException) {
                $this->assertTrue(true);
            }
            $this->assertSame($before, $item->fresh()->getAttributes());
            $this->assertDatabaseMissing('i_items', ['name' => 'Rejected late create']);
        } finally {
            InventoryItem::flushEventListeners();
            InventoryItem::clearBootedModels();
        }
    }

    /** @return array<string,array{string,bool}> */
    public static function operationTypes(): array
    {
        return ['material create' => ['material', true], 'material update' => ['material', false],
            'equipment create' => ['equipment', true], 'equipment update' => ['equipment', false]];
    }

    public function test_archived_classification_is_retained_and_categoryless_items_fail_closed(): void
    {
        [$lab, , $category, $payload] = $this->fixture('equipment', 'equipment', ['view', 'edit']);
        $item = InventoryItem::query()->create($payload + ['lab_id' => $lab->id]);
        $category->delete();
        $unknown = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Unclassified']);
        $this->get(route('vap-inventory.items.index'))->assertOk()->assertInertia(fn (Assert $page) => $page->has('items.data', 1)
            ->where('items.data.0.id', $item->id)->where('stats.equipment_count', 1));
        $this->get(route('vap-inventory.items.edit', $item))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('categories.0.id', $category->id)->where('categories.0.inventory_type', 'equipment'));
        $this->put(route('vap-inventory.items.update', $item), ['category_id' => $category->id, 'obs' => 'Retained category'])->assertRedirect();
        $this->get(route('vap-inventory.items.show', $unknown))->assertNotFound();
        $this->get(route('vap-inventory.items.edit', $unknown))->assertNotFound();
        $this->put(route('vap-inventory.items.update', $unknown), ['category_id' => null, 'name' => 'Guessed material'])->assertNotFound();
    }

    #[DataProvider('kindRoutes')]
    public function test_duplicate_entry_routes_redirect_and_old_writes_are_retired(string $type, string $prefix): void
    {
        [$lab, , , $payload] = $this->fixture($type, $type, ['view', 'add', 'edit']);
        $item = InventoryItem::query()->create($payload + ['lab_id' => $lab->id]);
        $this->get(route($prefix.'.index'))->assertRedirect(route('vap-inventory.items.index', ['inventory_type' => $type]));
        $this->get(route($prefix.'.create'))->assertRedirect(route('vap-inventory.items.create', ['inventory_type' => $type]));
        $this->get(route($prefix.'.edit', $item))->assertRedirect(route('vap-inventory.items.edit', $item));
        $this->get(route($prefix.'.show', $item))->assertRedirect(route('vap-inventory.items.show', $item));
        $this->assertFalse(Route::has($prefix.'.store'));
        $this->assertFalse(Route::has($prefix.'.update'));
        $this->post('/'.$prefix, $payload)->assertMethodNotAllowed();
        $this->assertContains($this->put('/'.$prefix.'/'.$item->id, ['name' => 'Retired write'])->status(), [404, 405]);
        $this->assertSame($payload['name'], $item->fresh()->name);
    }

    /** @return array<string,array{string,string}> */
    public static function kindRoutes(): array
    {
        return ['material' => ['material', 'iitems'], 'equipment' => ['equipment', 'iequipments']];
    }

    #[DataProvider('grants')]
    public function test_canonical_export_has_its_own_type_scoped_permission(string $grant): void
    {
        [$lab, , $material, $payload] = $this->fixture('material', $grant, ['export']);
        $equipment = ItemCategory::query()->create(['name' => fake()->uuid(), 'inventory_type' => 'equipment']);
        $items = ['material' => InventoryItem::query()->create($payload + ['lab_id' => $lab->id]),
            'equipment' => InventoryItem::query()->create(['name' => 'Exported instrument', 'lab_id' => $lab->id, 'category_id' => $equipment->id])];
        InventoryItem::query()->create(['name' => 'No classification', 'lab_id' => $lab->id]);
        InventoryItem::query()->create(['name' => 'Other laboratory', 'lab_id' => VAPLab::factory()->create()->id, 'category_id' => $material->id]);
        $material->delete();
        $equipment->delete();
        $this->get(route('vap-inventory.items.index'))->assertForbidden();
        Excel::fake();
        $response = $this->get(route('vap-inventory.items.export.inventory'));
        if ($grant === 'none') {
            $response->assertForbidden();
        } else {
            $response->assertOk();
            $expected = $grant === 'both' ? array_column($items, 'id') : [$items[$grant]->id];
            sort($expected);
            Excel::assertDownloaded('inventory_items.xlsx', fn (InventoryItemsExport $export): bool => $export->collection()->pluck('id')->sort()->values()->all() === $expected);
        }
    }

    #[DataProvider('typePermissions')]
    public function test_canonical_document_endpoints_check_the_item_kind(string $type, string $grant, bool $allowed): void
    {
        Storage::fake('public');
        Storage::fake('local');
        [$lab, , , $payload] = $this->fixture($type, $grant, ['view', 'edit']);
        $item = InventoryItem::query()->create($payload + ['lab_id' => $lab->id]);
        $media = InventoryItemDocumentMedia::factory()->create(['model_id' => $item->id]);
        Storage::disk('public')->put($media->getPathRelativeToRoot(), 'Retained test document');
        $otherCollection = InventoryItemDocumentMedia::factory()->create(['model_id' => $item->id, 'collection_name' => 'other']);
        $single = $this->getJson(route('vap-inventory.items.attachments.download-single', ['model_id' => $media->id]));
        $all = $this->get(route('vap-inventory.items.attachments.download-all', ['model_id' => $item->id]));
        if (! $allowed) {
            $single->assertForbidden();
            $all->assertForbidden();
            $this->delete(route('vap-inventory.items.attachments.delete', $media), ['model_id' => $item->id])->assertForbidden();
            $this->assertModelExists($media);
        } else {
            $single->assertOk();
            $this->assertSame('Retained test document', $single->streamedContent());
            $all->assertOk();
            $this->getJson(route('vap-inventory.items.attachments.download-single', ['model_id' => $otherCollection->id]))->assertNotFound();
            $this->delete(route('vap-inventory.items.attachments.delete', $otherCollection), ['model_id' => $item->id])->assertNotFound();
            $this->delete(route('vap-inventory.items.attachments.delete', $media), ['model_id' => $item->id, 'id' => $otherCollection->id])->assertRedirect();
            $this->assertSoftDeleted($media);
            Storage::disk($media->disk)->assertExists($media->getPathRelativeToRoot());
        }
        $this->assertModelExists($otherCollection);
    }

    public function test_kind_filter_rejects_malformed_input_and_unauthorized_creation_choices(): void
    {
        $this->fixture('material', 'material', ['view', 'add']);
        $this->getJson(route('vap-inventory.items.index', ['inventory_type' => ['equipment']]))
            ->assertUnprocessable()->assertJsonValidationErrors('inventory_type');
        $this->getJson(route('vap-inventory.items.create', ['inventory_type' => 'unknown']))
            ->assertUnprocessable()->assertJsonValidationErrors('inventory_type');
        $this->get(route('vap-inventory.items.create', ['inventory_type' => 'equipment']))->assertForbidden();
        $this->get(route('vap-inventory.items.create', ['inventory_type' => 'material']))->assertOk();
    }

    #[DataProvider('kindRoutes')]
    public function test_legacy_exports_redirect_with_a_frozen_kind(string $type, string $prefix): void
    {
        $this->fixture($type, $type, ['export']);
        $otherPrefix = $type === 'material' ? 'iequipments' : 'iitems';
        $this->get(route($otherPrefix.'.export'))->assertForbidden();
        $this->get(route($prefix.'.export', ['inventory_type' => $type === 'material' ? 'equipment' : 'material']))
            ->assertRedirect(route('vap-inventory.items.export.inventory', ['inventory_type' => $type]));
    }

    public function test_export_kind_and_category_filters_cannot_broaden_the_authorized_set(): void
    {
        [$lab, , $material, $payload] = $this->fixture('material', 'material', ['export']);
        InventoryItem::query()->create($payload + ['lab_id' => $lab->id]);
        $equipment = ItemCategory::query()->create(['name' => fake()->uuid(), 'inventory_type' => 'equipment']);
        InventoryItem::query()->create(['name' => 'Not authorized', 'lab_id' => $lab->id, 'category_id' => $equipment->id]);
        foreach ([['inventory_type' => 'equipment'], ['category_id' => $equipment->id]] as $filter) {
            Excel::fake();
            $this->get(route('vap-inventory.items.export.inventory', $filter))->assertOk();
            Excel::assertDownloaded('inventory_items.xlsx', fn (InventoryItemsExport $export): bool => $export->collection()->isEmpty());
        }
        $this->getJson(route('vap-inventory.items.export.inventory', ['inventory_type' => ['equipment']]))
            ->assertUnprocessable()->assertJsonValidationErrors('inventory_type');
        $this->getJson(route('vap-inventory.items.export.inventory', ['category_id' => [$material->id]]))
            ->assertUnprocessable()->assertJsonValidationErrors('category_id');
    }

    public function test_filtered_controls_use_the_selected_kind_capability(): void
    {
        [$lab, $user, , $payload] = $this->fixture('material', 'both', ['view']);
        $item = InventoryItem::query()->create($payload + ['lab_id' => $lab->id]);
        foreach (['add', 'edit', 'delete', 'export'] as $ability) {
            $user->givePermissionTo(Permission::findOrCreate($ability.'_iitems', 'web'));
        }
        $this->get(route('vap-inventory.items.index', ['inventory_type' => 'equipment']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('canCreate', false)->where('canExport', false));
        $this->get(route('vap-inventory.items.index', ['inventory_type' => 'material']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('canCreate', true)->where('canExport', true)
                ->where('items.data.0.can_edit', true)->where('items.data.0.can_delete', true));
        $this->get(route('vap-inventory.items.show', $item))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('canEdit', true));
    }

    #[DataProvider('kindRoutes')]
    public function test_legacy_documents_cannot_bypass_kind_permissions(string $type, string $prefix): void
    {
        Storage::fake('public');
        Storage::fake('local');
        [$lab, $user, , $payload] = $this->fixture($type, $type, ['view', 'edit']);
        $item = InventoryItem::query()->create($payload + ['lab_id' => $lab->id]);
        $media = InventoryItemDocumentMedia::factory()->create(['model_id' => $item->id]);
        Storage::disk('public')->put($media->getPathRelativeToRoot(), 'Owned legacy document');
        $otherPrefix = $type === 'material' ? 'iequipments' : 'iitems';
        $this->get(route($otherPrefix.'.download-all-attachments', ['model_id' => $item->id]))->assertNotFound();
        $this->get(route($otherPrefix.'.download-single-attachment', ['model_id' => $media->id]))->assertNotFound();
        $this->delete(route($otherPrefix.'.delete-attachment'), ['model_id' => $item->id, 'id' => $media->id])->assertNotFound();
        $single = $this->get(route($prefix.'.download-single-attachment', ['model_id' => $media->id]));
        $single->assertOk();
        $this->assertSame('Owned legacy document', $single->streamedContent());
        $this->get(route($prefix.'.download-all-attachments', ['model_id' => $item->id]))->assertOk();
        $user->revokePermissionTo('view_'.$prefix);
        $this->get(route($prefix.'.download-single-attachment', ['model_id' => $media->id]))->assertForbidden();
        $user->revokePermissionTo('edit_'.$prefix);
        $this->delete(route($prefix.'.delete-attachment'), ['model_id' => $item->id, 'id' => $media->id])->assertForbidden();
        $this->assertModelExists($media);
    }

    public function test_legacy_target_and_bulk_paths_cannot_reclassify_permission_scope(): void
    {
        [$lab, , , $payload] = $this->fixture('material', 'both', ['view', 'edit', 'delete', 'restore']);
        $material = InventoryItem::query()->create($payload + ['lab_id' => $lab->id]);
        $category = ItemCategory::query()->create(['name' => fake()->uuid(), 'inventory_type' => 'equipment']);
        $equipment = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Other kind', 'category_id' => $category->id]);
        $this->get(route('iitems.show', $equipment))->assertNotFound();
        $this->get(route('iequipments.edit', $material))->assertNotFound();
        $this->delete(route('iitems.destroy'), ['recordIds' => [$material->id, $equipment->id]])->assertNotFound();
        $this->assertFalse($material->fresh()->trashed());
        $this->assertFalse($equipment->fresh()->trashed());
    }

    /** @param list<string> $abilities @return array{VAPLab,User,ItemCategory,array<string,mixed>} */
    private function fixture(string $type, string $grant, array $abilities): array
    {
        $lab = VAPLab::factory()->create();
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        foreach (['material' => 'iitems', 'equipment' => 'iequipments'] as $kind => $module) {
            if ($grant === $kind || $grant === 'both') {
                foreach ($abilities as $ability) {
                    $user->givePermissionTo(Permission::findOrCreate($ability.'_'.$module, 'web'));
                }
            }
        }
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $category = ItemCategory::query()->create(['name' => ($type === 'material' ? 'equipamentos is only a name ' : 'Renamed family ').fake()->uuid(),
            'code' => fake()->uuid(), 'inventory_type' => $type]);
        $unit = InventoryUnit::query()->create(['name' => 'Unit '.fake()->uuid(), 'code' => fake()->uuid()]);

        return [$lab, $user, $category, ['name' => 'Item '.fake()->uuid(), 'category_id' => $category->id, 'unit_id' => $unit->id]];
    }
}
