<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryItemShowTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        return Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->firstOrFail();
    }

    public function test_inventory_item_show_exposes_chart_payloads(): void
    {
        $user = $this->verifiedAdmin();
        $item = InventoryItem::query()->with('inventory')->firstOrFail();

        $this->actingAs($user)
            ->get(route('vap-inventory.items.show', $item))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPInventory/Items/Show')
                ->where('item.id', $item->id)
                ->has('charts.stock_distribution.labels')
                ->has('charts.stock_distribution.series')
                ->where('charts.activity_mix.labels.0', 'Transações')
                ->where('charts.compliance_pulse.labels.0', 'Estoque total')
            );
    }
}
