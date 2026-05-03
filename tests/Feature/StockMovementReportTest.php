<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StockMovementReportTest extends TestCase
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

    public function test_stock_movement_report_exposes_chart_payloads(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->get(route('vap-inventory.reports.stock-movement', ['view' => 'summary']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPInventory/Reports/StockMovement')
                ->has('charts.direction_breakdown.labels')
                ->has('charts.direction_breakdown.series')
                ->has('charts.type_mix.labels')
                ->has('charts.type_mix.series')
                ->has('charts.daily_activity.labels')
                ->has('charts.daily_activity.series')
            );
    }
}
