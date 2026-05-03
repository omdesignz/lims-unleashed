<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryValueReportTest extends TestCase
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

    public function test_inventory_value_report_exposes_chart_payloads(): void
    {
        $user = $this->verifiedAdmin();

        $response = $this->actingAs($user)->get(route('vap-inventory.reports.inventory-value'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPInventory/Reports/InventoryValue')
                ->has('charts.category_value_breakdown.labels')
                ->has('charts.category_value_breakdown.series')
                ->has('charts.warehouse_value_breakdown.labels')
                ->has('charts.warehouse_value_breakdown.series')
                ->has('charts.top_item_value.labels')
                ->has('charts.top_item_value.series')
            );
    }
}
