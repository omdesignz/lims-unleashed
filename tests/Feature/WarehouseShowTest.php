<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\PortalPasswordResetNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WarehouseShowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_warehouse_show_exposes_stats_charts_and_recent_activity_payload(): void
    {
        $user = User::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();

        $this->actingAs($user)
            ->get(route('warehouses.show', $warehouse->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Warehouses/Show')
                ->where('record.data.id', $warehouse->id)
                ->has('stats.invoices')
                ->has('stats.collections')
                ->where('charts.account_health.labels.0', 'Faturas pagas')
                ->where('charts.operations.labels.0', 'Colheitas processadas')
                ->where('charts.documents.labels.0', 'Proformas')
            );
    }

    public function test_admin_can_send_portal_password_reset_to_warehouse(): void
    {
        Notification::fake();

        $user = User::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();

        $warehouse->forceFill([
            'email' => 'warehouse-reset-'.$warehouse->id.'@lims-unleashed.test',
        ])->save();

        $this->actingAs($user)
            ->post(route('warehouses.send-password-reset', $warehouse))
            ->assertRedirect();

        Notification::assertSentTo($warehouse, PortalPasswordResetNotification::class);
    }
}
