<?php

namespace Tests\Feature;

use App\Models\LabNetwork;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LabNetworkAccessTest extends TestCase
{
    use DatabaseTransactions;

    private function member(VAPLab $lab, bool $overview = false, bool $branding = false): User
    {
        $user = User::factory()->create(['is_active' => true]);
        DB::table('lab_user')->insert(['user_id' => $user->id, 'lab_id' => $lab->id, 'can_view_network' => $overview, 'can_manage_branding' => $branding]);

        return $user;
    }

    private function network(): array
    {
        $network = LabNetwork::factory()->create();
        $main = VAPLab::factory()->create(['network_id' => $network->id]);
        $peer = VAPLab::factory()->create(['network_id' => $network->id]);
        $network->update(['main_lab_id' => $main->id]);

        return [$network, $main, $peer];
    }

    private function stock(VAPLab $lab, string $name, string $status = 'AVAILABLE', ?string $expiry = null): int
    {
        $warehouse = DB::table('i_warehouses')->insertGetId(['name' => 'Warehouse', 'lab_id' => $lab->id]);
        $item = DB::table('i_items')->insertGetId(['lab_id' => $lab->id, 'name' => $name, 'reagent_expiry_date' => $expiry]);

        return DB::table('inventory')->insertGetId(['lab_id' => $lab->id, 'warehouse_id' => $warehouse, 'item_id' => $item, 'qty_available' => 12, 'status' => $status]);
    }

    public function test_main_lab_can_see_only_network_stock_and_summary_indicators(): void
    {
        [$network, $main, $peer] = $this->network();
        $this->stock($main, 'Ethanol');
        $this->stock($peer, 'Membranes');
        $this->stock(VAPLab::factory()->create(), 'Private unrelated stock');
        DB::table('sample_entries')->insert(['name' => 'Private patient sample', 'lab_id' => $peer->id]);

        $this->actingAs($this->member($main, true))->get(route('lab-network.index', $network))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('LabNetwork/Index')
            ->has('labs', 2)->has('stock.data', 2)->where('networkOverview', true)
            ->missing('customers')->missing('results')->missing('samples')
            ->missing('stock.data.0.item_id')->missing('stock.data.0.customer_id'));
    }

    public function test_network_material_search_does_not_grant_access_to_a_peer_catalog_record(): void
    {
        [$network, $main, $peer] = $this->network();
        $stockId = $this->stock($peer, 'Peer-only solvent');
        $itemId = DB::table('inventory')->where('id', $stockId)->value('item_id');
        $user = $this->member($main, true);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $this->withSession(['active_lab_id' => $main->id]);

        $this->actingAs($user)->get(route('lab-network.index', [$network, 'search' => 'Peer-only']))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('stock.data', 1)
            ->where('stock.data.0.name', 'Peer-only solvent')
            ->missing('stock.data.0.item_id'));

        $this->get(route('vap-inventory.items.show', $itemId))->assertNotFound();
        $this->put(route('vap-inventory.items.update', $itemId), ['name' => 'Changed by network viewer'])
            ->assertNotFound();
        $this->get(route('iitems.getInventoryItem', ['q' => 'Peer-only']))->assertExactJson([]);
        $this->assertDatabaseHas('i_items', ['id' => $itemId, 'lab_id' => $peer->id, 'name' => 'Peer-only solvent']);
    }

    public function test_peer_membership_does_not_grant_network_overview_even_with_the_flag(): void
    {
        [$network, $main, $peer] = $this->network();
        $this->stock($main, 'Main only');
        $this->stock($peer, 'Peer only');
        $user = $this->member($peer, true);

        $this->actingAs($user)->get(route('lab-network.index', $network))
            ->assertInertia(fn (Assert $page) => $page->has('labs', 1)->has('stock.data', 1)
                ->where('stock.data.0.name', 'Peer only')->where('networkOverview', false));
        $this->get(route('lab-network.index', [$network, 'lab_id' => $main->id]))->assertSessionHasErrors('lab_id');
    }

    public function test_global_admin_without_membership_cannot_bypass_network_boundary(): void
    {
        [$network] = $this->network();
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

        $this->actingAs($user)->get(route('lab-network.index', $network))->assertForbidden();
    }

    public function test_search_is_case_insensitive_and_blocked_or_expired_stock_is_not_available(): void
    {
        [$network, $main] = $this->network();
        $this->stock($main, 'ETHANOL');
        $this->stock($main, 'Ethanol quarantined', 'ON_HOLD');
        $this->stock($main, 'Ethanol expired', 'AVAILABLE', now()->subDay()->toDateString());
        $this->stock($main, 'Other material');

        $this->actingAs($this->member($main, true))->get(route('lab-network.index', [$network, 'search' => 'ethanol', 'available' => 1]))
            ->assertInertia(fn (Assert $page) => $page->has('stock.data', 1)->where('stock.data.0.name', 'ETHANOL')->where('stock.data.0.available_quantity', '12.0000'));
    }

    public function test_branding_is_separately_permissioned_and_can_return_to_inheritance(): void
    {
        [$network, $main, $peer] = $this->network();
        $viewer = $this->member($main, true);
        $this->actingAs($viewer)->put(route('lab-branding.update', $main), ['primary_color' => '#336699'])->assertForbidden();
        $editor = $this->member($main, true, true);
        $this->actingAs($editor)->put(route('lab-branding.update', $peer), ['primary_color' => '#336699'])->assertForbidden();
        $this->put(route('lab-branding.update', $main), ['primary_color' => 'red; background:url(evil)'])->assertSessionHasErrors('primary_color');
        $this->put(route('lab-branding.update', $main), ['primary_color' => '#336699'])->assertRedirect();
        $this->assertSame('#336699', $main->fresh()->primary_color);
        $this->put(route('lab-branding.update', $main), ['primary_color' => null])->assertRedirect();
        $this->assertNull($main->fresh()->primary_color);
        $this->get(route('lab-network.index', $network))->assertInertia(fn (Assert $page) => $page->where('laboratory.active_lab.primary_color', '#24664f'));
    }

    public function test_context_switch_requires_direct_membership_not_network_visibility(): void
    {
        [$network, $main, $peer] = $this->network();
        $this->actingAs($this->member($main, true))->post(route('lab-context.switch', $peer))->assertForbidden();
        $this->post(route('lab-context.switch', $main))->assertRedirect(route('lab-network.index', $network))->assertSessionHas('active_lab_id', $main->id);
    }

    public function test_deleted_main_lab_revokes_network_access(): void
    {
        [$network, $main] = $this->network();
        $user = $this->member($main, true);
        $main->delete();

        $this->actingAs($user)->get(route('lab-network.index', $network))->assertForbidden();
    }
}
