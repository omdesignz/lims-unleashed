<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ConsumptionReportTest extends TestCase
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
