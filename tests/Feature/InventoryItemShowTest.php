<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\ItemCategory;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryItemShowTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $admin->id]);
        $this->withSession(['active_lab_id' => $lab->id]);

        return $admin;
    }

    public function test_inventory_item_show_exposes_chart_payloads(): void
    {
        $user = $this->verifiedAdmin();
        $item = InventoryItem::query()->create([
            'category_id' => ItemCategory::query()->create(['name' => 'Chart fixture materials'])->id,
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
            'name' => 'Inventory item chart fixture',
            'code' => fake()->unique()->bothify('CH-######'),
        ]);
        $warehouse = InventoryItemWarehouse::query()->create([
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
            'name' => 'Inventory chart warehouse',
        ]);
        Inventory::query()->create(['item_id' => $item->id, 'warehouse_id' => $warehouse->id]);

        $this->actingAs($user)
            ->get(route('vap-inventory.items.show', $item))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPInventory/Items/Show')
                ->where('item.id', $item->id)
                ->has('charts.stock_distribution.labels')
                ->has('charts.stock_distribution.series')
                ->where('charts.activity_mix.labels.0', 'Transacções')
                ->where('charts.compliance_pulse.labels.0', 'Existências totais')
            );
    }
}
