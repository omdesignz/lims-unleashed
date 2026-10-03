<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Notifications\PortalPasswordResetNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WarehouseShowTest extends TestCase
{
    use DatabaseTransactions;

    /** @return array{User, VAPLab, Warehouse} */
    private function siteFixture(): array
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->withSession(['active_lab_id' => $lab->id]);

        $customer = Customer::query()->create(['name' => 'Warehouse show customer '.fake()->uuid()]);
        $warehouse = Warehouse::query()->create([
            'name' => 'Warehouse show site',
            'customer_id' => $customer->id,
            'email' => 'warehouse-show-'.fake()->uuid().'@lims-unleashed.test',
        ]);

        return [$user, $lab, $warehouse];
    }

    public function test_shared_warehouse_show_exposes_only_active_laboratory_sample_activity(): void
    {
        [$user, $lab, $warehouse] = $this->siteFixture();
        $peerLab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $peerLab->id, 'user_id' => $user->id]);
        $local = VAPSampleEntry::factory()->create([
            'lab_id' => $lab->id,
            'customer_id' => $warehouse->customer_id,
            'warehouse_id' => $warehouse->id,
            'name' => 'Amostra local do local',
            'status' => 'POR_INICIAR',
        ]);
        $peer = VAPSampleEntry::factory()->create([
            'lab_id' => $peerLab->id,
            'customer_id' => $warehouse->customer_id,
            'warehouse_id' => $warehouse->id,
            'name' => 'Amostra privada de outro laboratório',
            'status' => 'COMPLETADO',
        ]);
        $otherCustomer = Customer::query()->create(['name' => 'Unrelated sample customer '.fake()->uuid()]);
        VAPSampleEntry::factory()->create([
            'lab_id' => $lab->id,
            'customer_id' => $otherCustomer->id,
            'warehouse_id' => $warehouse->id,
            'name' => 'Inconsistent site lineage',
        ]);

        $this->actingAs($user)
            ->get(route('warehouses.show', $warehouse->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Warehouses/Show')
                ->where('record.data.id', $warehouse->id)
                ->where('siteState.summary.total_samples', 1)
                ->where('siteState.summary.samples_in_progress', 1)
                ->where('siteState.summary.completed_samples', 0)
                ->has('siteState.recent_samples', 1)
                ->where('siteState.recent_samples.0.id', $local->id)
            );

        $response = $this->actingAs($user)->get(route('warehouses.show', $warehouse));
        $this->assertArrayNotHasKey('stats', $response->inertiaProps());
        $this->assertArrayNotHasKey('charts', $response->inertiaProps());
        $this->assertArrayNotHasKey('recentActivity', $response->inertiaProps());

        $this->withSession(['active_lab_id' => $peerLab->id])
            ->get(route('warehouses.show', $warehouse))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('siteState.summary.total_samples', 1)
                ->where('siteState.summary.samples_in_progress', 0)
                ->where('siteState.summary.completed_samples', 1)
                ->has('siteState.recent_samples', 1)
                ->where('siteState.recent_samples.0.id', $peer->id)
            );
    }

    public function test_admin_can_send_portal_password_reset_to_warehouse(): void
    {
        Notification::fake();

        [$user, , $warehouse] = $this->siteFixture();

        $this->actingAs($user)
            ->post(route('warehouses.send-password-reset', $warehouse))
            ->assertRedirect();

        Notification::assertSentTo($warehouse, PortalPasswordResetNotification::class);
    }

    public function test_site_credentials_require_edit_permission_and_direct_laboratory_membership(): void
    {
        [$user, , $warehouse] = $this->siteFixture();
        $password = 'CorrectHorse123!';
        $attemptedPassword = 'DifferentHorse456!';

        $this->actingAs($user)->put(route('warehouses.setpass', $warehouse), [
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertRedirect();
        $this->assertTrue(Hash::check($password, (string) $warehouse->fresh()->password));
        $originalPassword = $warehouse->fresh()->password;

        DB::table('lab_user')->where('user_id', $user->id)->delete();

        $this->actingAs($user)
            ->get(route('warehouses.show', $warehouse))
            ->assertForbidden();
        $this->put(route('warehouses.setpass', $warehouse), [
            'password' => $attemptedPassword,
            'password_confirmation' => $attemptedPassword,
        ])->assertForbidden();
        $this->put(route('warehouses.setpass', $warehouse), ['password' => 'short'])->assertForbidden();
        $this->post(route('warehouses.send-password-reset', $warehouse))->assertForbidden();
        $this->assertSame($originalPassword, $warehouse->fresh()->password);

        $otherUser = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $otherUser->assignRole(Role::findOrCreate('employee', 'web'));
        $this->actingAs($otherUser)->put(route('warehouses.setpass', $warehouse), [
            'password' => $attemptedPassword,
            'password_confirmation' => $attemptedPassword,
        ])->assertForbidden();
        $this->assertFalse(Hash::check($attemptedPassword, (string) $warehouse->fresh()->password));
    }

    public function test_unknown_site_identifiers_return_not_found(): void
    {
        [$user] = $this->siteFixture();
        $password = 'CorrectHorse123!';

        $this->actingAs($user)
            ->get(route('warehouses.show', ['warehouse' => 'unknown-site']))
            ->assertNotFound();
        $this->put(route('warehouses.setpass', ['warehouse' => 'unknown-site']), [
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertNotFound();
    }
}
