<?php

namespace Tests\Feature;

use App\Http\Resources\InventoryResource;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryUnit;
use App\Models\ItemCategory;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InventoryPositionClassificationTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('readCases')]
    public function test_stock_reads_retain_explicit_classification_and_labels(string $route, string $type, string $state): void
    {
        $lab = VAPLab::factory()->create();
        $this->operator($lab, ['view_inventory', 'edit_inventory', 'view_iitems', 'view_iequipments']);
        $stock = $this->stock($lab, $type);
        $item = $stock->item;
        $category = $item->category;
        if ($state === 'item_archived') {
            $item->delete();
        } elseif ($state === 'category_archived') {
            $category->delete();
        }
        $path = match ($route) {
            'index' => 'record.data.0',
            'edit' => 'initialRecord.data',
            default => 'record.data',
        };
        $url = route('inventory.'.$route, $route === 'index' ? [] : ['inventory' => $stock->id]);
        if ($route === 'edit') {
            $destination = route('inventory.index', ['edit' => $stock->id]);
            $this->get($url)->assertRedirect($destination);
            $url = $destination;
        }
        $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where($path.'.id', $stock->id)
            ->where($path.'.item', $item->name)
            ->where($path.'.category', $category->name)
            ->where($path.'.inventory_type', $type)
            ->where($path.'.can_open_item', $state !== 'item_archived')
            ->where($path.'.min_stock_level', '0.1250')
            ->where($path.'.reorder_point', '0.5000'));
        $this->assertSame('2.0000', $stock->fresh()->qty_available);
    }

    /** @return array<string, array{string, string, string}> */
    public static function readCases(): array
    {
        $cases = [];
        foreach (['index', 'show', 'edit'] as $route) {
            foreach (['material', 'equipment'] as $type) {
                foreach (['active', 'item_archived', 'category_archived'] as $state) {
                    $cases[$route.'-'.$type.'-'.$state] = [$route, $type, $state];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('permissionCases')]
    public function test_item_navigation_uses_only_the_matching_kind_permission(string $type, ?string $permission, bool $allowed): void
    {
        $lab = VAPLab::factory()->create();
        $this->operator($lab, array_filter(['view_inventory', $permission]));
        $stock = $this->stock($lab, $type);
        $this->get(route('inventory.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('record.data.0.id', $stock->id)
            ->where('record.data.0.inventory_type', $type)
            ->where('record.data.0.can_open_item', $allowed));
    }

    /** @return array<string, array{string, ?string, bool}> */
    public static function permissionCases(): array
    {
        return [
            'material-read' => ['material', 'view_iitems', true],
            'material-wrong-read' => ['material', 'view_iequipments', false],
            'material-stock-only' => ['material', null, false],
            'equipment-read' => ['equipment', 'view_iequipments', true],
            'equipment-wrong-read' => ['equipment', 'view_iitems', false],
            'equipment-stock-only' => ['equipment', null, false],
        ];
    }

    public function test_peer_lab_positions_are_not_listed_or_readable(): void
    {
        $lab = VAPLab::factory()->create();
        $this->operator($lab, ['view_inventory', 'edit_inventory', 'view_iitems']);
        $own = $this->stock($lab, 'material');
        $peer = $this->stock(VAPLab::factory()->create(), 'material');
        $this->get(route('inventory.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('record.data', 1)->where('record.data.0.id', $own->id));
        $this->get(route('inventory.show', $peer))->assertNotFound();
        $this->get(route('inventory.edit', $peer))->assertNotFound();
    }

    public function test_unclassified_resource_does_not_guess_material_or_allow_navigation(): void
    {
        $lab = VAPLab::factory()->create();
        $this->operator($lab, ['view_inventory', 'view_iitems', 'view_iequipments']);
        $stock = $this->stock($lab, 'material');
        $stock->item->setRelation('category', null);
        $data = InventoryResource::make($stock)->resolve(request());
        $this->assertNull($data['inventory_type']);
        $this->assertFalse($data['can_open_item']);
    }

    public function test_listing_batches_retained_item_and_category_reads(): void
    {
        $lab = VAPLab::factory()->create();
        $this->operator($lab, ['view_inventory']);
        foreach (['material', 'equipment', 'material', 'equipment'] as $type) {
            $stock = $this->stock($lab, $type);
            $stock->item->category->delete();
            $stock->item->delete();
        }
        DB::enableQueryLog();
        try {
            $this->get(route('inventory.index'))->assertOk()->assertInertia(fn (Assert $page) => $page->has('record.data', 4));
            $queries = array_column(DB::getQueryLog(), 'query');
            foreach (['i_items', 'item_categories'] as $table) {
                $reads = array_filter($queries, fn (string $query): bool => preg_match('/^select .* from "'.$table.'" /s', $query) === 1);
                $this->assertCount(1, $reads, 'Retained '.$table.' must be eager loaded once, not queried per row.');
            }
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    #[DataProvider('searchCases')]
    public function test_search_matches_retained_category_labels_without_exposing_peer_stock(string $type, string $state): void
    {
        $lab = VAPLab::factory()->create();
        $this->operator($lab, ['view_inventory']);
        $stock = $this->stock($lab, $type);
        $item = $stock->item;
        $category = $item->category;
        $peer = $this->stock(VAPLab::factory()->create(), $type);
        $peer->item->category->update(['name' => $category->name]);
        if ($state === 'item_archived') {
            $item->delete();
        } elseif ($state === 'category_archived') {
            $category->delete();
        }
        $this->get(route('inventory.index', ['search' => $category->name]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('record.data', 1)
                ->where('record.data.0.id', $stock->id)
                ->where('record.data.0.category', $category->name)
                ->where('record.data.0.inventory_type', $type)
                ->where('record.data.0.can_open_item', false));
    }

    /** @return array<string, array{string, string}> */
    public static function searchCases(): array
    {
        return [
            'active-material' => ['material', 'active'],
            'archived-material-item' => ['material', 'item_archived'],
            'archived-material-category' => ['material', 'category_archived'],
            'active-equipment' => ['equipment', 'active'],
            'archived-equipment-item' => ['equipment', 'item_archived'],
            'archived-equipment-category' => ['equipment', 'category_archived'],
        ];
    }

    /** @param list<string> $permissions */
    private function operator(VAPLab $lab, array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        return $user;
    }

    private function stock(VAPLab $lab, string $type): Inventory
    {
        $category = ItemCategory::query()->create(['name' => 'Category '.fake()->uuid(), 'inventory_type' => $type]);
        $unit = InventoryUnit::query()->create(['code' => fake()->uuid(), 'description' => 'Units']);
        $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Item '.fake()->uuid(), 'category_id' => $category->id, 'unit_id' => $unit->id]);
        $warehouse = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Warehouse '.fake()->uuid()]);

        return Inventory::query()->create(['item_id' => $item->id, 'warehouse_id' => $warehouse->id,
            'qty_available' => '2.0000', 'min_stock_level' => '0.1250', 'reorder_point' => '0.5000']);
    }
}
