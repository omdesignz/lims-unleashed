<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ConsumptionReportTest extends TestCase
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

    public function test_consumption_report_exposes_chart_payloads(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->get(route('vap-inventory.reports.consumption'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPInventory/Reports/Consumption')
                ->has('charts.item_consumption.labels')
                ->has('charts.item_consumption.series')
                ->has('charts.user_consumption.labels')
                ->has('charts.user_consumption.series')
                ->has('charts.daily_consumption.labels')
                ->has('charts.daily_consumption.series')
            );
    }
}
