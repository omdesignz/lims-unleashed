<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\ItemCategory;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryItemLaboratoryOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    private function operator(VAPLab $lab): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->withSession(['active_lab_id' => $lab->id]);

        return $user;
    }

    public function test_catalog_identifiers_are_lab_scoped_and_peer_direct_urls_are_private(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $category = ItemCategory::query()->create(['name' => 'Owned materials '.fake()->uuid()]);
        $local = InventoryItem::query()->create([
            'lab_id' => $lab->id, 'name' => 'Common material', 'category_id' => $category->id,
            'code' => 'COMMON-01', 'barcode' => 'BAR-01',
        ]);
        $foreign = InventoryItem::query()->create([
            'lab_id' => $peer->id, 'name' => 'Common material', 'category_id' => $category->id,
            'code' => 'COMMON-01', 'barcode' => 'BAR-01',
        ]);

        $this->assertSame($local->internal_code, $foreign->internal_code);
        $this->actingAs($user)->get(route('vap-inventory.items.index'))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('VAPInventory/Items/Index')
            ->has('items.data', 1)
            ->where('items.data.0.id', $local->id));

        $this->get(route('vap-inventory.items.show', $foreign))->assertNotFound();
        $this->get(route('vap-inventory.items.edit', $foreign))->assertNotFound();
        $this->put(route('vap-inventory.items.update', $foreign), [
            'name' => 'Stolen', 'category_id' => $category->id,
        ])->assertNotFound();
        $this->delete(route('vap-inventory.items.destroy', $foreign))->assertNotFound();
        $this->assertSame('Common material', $foreign->fresh()->name);
    }

    public function test_database_rejects_unowned_items_and_stock_in_a_peer_warehouse(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Owned item']);
        $localWarehouse = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Local']);
        $peerWarehouse = InventoryItemWarehouse::query()->create(['lab_id' => $peer->id, 'name' => 'Peer']);

        $this->assertConstraintFails(fn () => DB::table('i_items')->insert(['name' => 'No owner']));
        $this->assertConstraintFails(fn () => Inventory::query()->create([
            'item_id' => $item->id, 'warehouse_id' => $peerWarehouse->id, 'qty_available' => 2,
        ]));
        $this->assertConstraintFails(fn () => DB::table('inventory')->insert([
            'item_id' => $item->id, 'warehouse_id' => $localWarehouse->id, 'qty_available' => 2,
        ]));

        $this->assertDatabaseCount('inventory', 0);
    }

    public function test_legacy_lookup_and_bulk_archive_cannot_cross_laboratories(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $local = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Local material']);
        $foreign = InventoryItem::query()->create(['lab_id' => $peer->id, 'name' => 'Peer material', 'code' => 'PEER']);

        $this->actingAs($user)->get(route('iitems.getInventoryItem', ['q' => 'Peer']))
            ->assertOk()->assertExactJson([]);
        $this->get(route('iitems.show', $foreign))->assertNotFound();
        $this->get(route('iitems.edit', $foreign))->assertNotFound();
        $this->delete(route('iitems.destroy'), ['recordIds' => [$local->id, $foreign->id]])->assertNotFound();
        $this->assertFalse($local->fresh()->trashed());
        $this->assertFalse($foreign->fresh()->trashed());
    }

    public function test_legacy_search_does_not_bypass_laboratory_filter_for_code_matches(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $category = ItemCategory::query()->create(['name' => 'Search materials '.fake()->uuid()]);

        if ($category->id === 1) {
            $category = ItemCategory::query()->create(['name' => 'Other materials '.fake()->uuid()]);
        }

        InventoryItem::query()->create([
            'lab_id' => $peer->id,
            'category_id' => $category->id,
            'name' => 'Peer material',
            'code' => 'PEER-SEARCH',
        ]);

        $this->actingAs($user)->get(route('iitems.index', ['search' => 'PEER-SEARCH']))
            ->assertRedirect(route('vap-inventory.items.index', ['inventory_type' => 'material', 'search' => 'PEER-SEARCH']));
        $this->get(route('vap-inventory.items.index', ['inventory_type' => 'material', 'search' => 'PEER-SEARCH']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPInventory/Items/Index')
                ->has('items.data', 0));
    }

    private function assertConstraintFails(callable $operation): void
    {
        try {
            DB::transaction($operation);
            $this->fail('Expected PostgreSQL to reject the cross-laboratory inventory record.');
        } catch (QueryException $exception) {
            $this->assertNotEmpty($exception->getCode());
        }
    }
}
