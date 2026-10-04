<?php

namespace Tests\Feature;

use App\Actions\CreateSupportingModulesDemo;
use App\Models\InventoryNeed;
use App\Models\MaintenanceTask;
use App\Models\NotificationTemplate;
use App\Models\PersonnelQualification;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StaffAccountAccess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use LogicException;
use Tests\TestCase;

class SupportingModulesDemoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_fresh_demo_accounts_use_real_login_and_existing_accounts_are_unchanged(): void
    {
        $existing = User::factory()->create();
        $existingBefore = $existing->fresh()->getRawOriginal();
        $role = Role::findOrCreate('admin', 'web');
        $permissionsBefore = $role->permissions()->pluck('id')->all();
        Notification::fake();
        $demo = app(CreateSupportingModulesDemo::class)->execute();
        $this->assertSame($existingBefore, $existing->fresh()->getRawOriginal());
        $this->assertSame($permissionsBefore, $role->permissions()->pluck('id')->all());
        $staff = User::findOrFail($demo['staff']['id']);
        $reviewer = User::findOrFail($demo['reviewer']['id']);
        $manager = User::findOrFail($demo['lab_manager']['id']);
        $qualificationEditor = User::findOrFail($demo['qualification_editor']['id']);
        $peerTarget = User::findOrFail($demo['peer_target']['id']);
        $portal = Warehouse::findOrFail($demo['portal']['id']);
        $this->assertTrue($staff->hasRole('admin'));
        $this->assertFalse($reviewer->hasRole('admin'));
        $this->assertTrue($reviewer->can('view_maintenance_tasks'));
        $this->assertFalse($reviewer->can('edit_maintenance_tasks'));
        $this->assertFalse($manager->hasRole('admin'));
        $this->assertSame(['add_users', 'delete_users', 'view_users'], $manager->getAllPermissions()->pluck('name')->sort()->values()->all());
        $this->assertTrue($qualificationEditor->roles->isEmpty());
        $this->assertSame(['edit_users', 'view_users'], $qualificationEditor->getAllPermissions()->pluck('name')->sort()->values()->all());
        $this->assertTrue($peerTarget->roles->isEmpty());
        $this->assertTrue($peerTarget->getAllPermissions()->isEmpty());
        foreach (['staff' => $staff, 'reviewer' => $reviewer, 'lab_manager' => $manager, 'qualification_editor' => $qualificationEditor, 'peer_target' => $peerTarget, 'portal' => $portal] as $key => $account) {
            $this->assertTrue(Hash::check($demo[$key]['password'], $account->password));
            $this->assertSame(40, strlen($demo[$key]['password']));
            $this->assertNotNull($account->email_verified_at);
        }
        $this->assertCount(6, array_unique(array_map(fn (string $key): string => $demo[$key]['password'], ['staff', 'reviewer', 'lab_manager', 'qualification_editor', 'peer_target', 'portal'])));
        $this->assertSame($demo['main_lab_id'], InventoryNeed::findOrFail($demo['need_id'])->lab_id);
        $this->assertSame(2, MaintenanceTask::whereKey($demo['maintenance_task_ids'])->count());
        $this->assertSame(1, DB::table('lab_user')->where('user_id', $staff->id)->count());
        $this->assertFalse(DB::table('lab_user')->where('user_id', $staff->id)->where('lab_id', $demo['peer_lab_id'])->exists());
        foreach (NotificationTemplate::whereIn('lab_id', [$demo['main_lab_id'], $demo['peer_lab_id']])->get() as $template) {
            $this->assertSame(['database'], $template->channels);
        }
        Notification::assertNothingSent();

        $this->post(route('login'), ['email' => $staff->email, 'password' => $demo['staff']['password']])
            ->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($staff);
        $this->post(route('logout'))->assertRedirect();
        $this->post(route('login'), ['email' => $manager->email, 'password' => $demo['lab_manager']['password']])
            ->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($manager);
        $this->post(route('logout'))->assertRedirect();
        $this->post(route('login'), ['email' => $qualificationEditor->email, 'password' => $demo['qualification_editor']['password']])
            ->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($qualificationEditor);
        $this->post(route('logout'))->assertRedirect();
        $this->post(route('portal.login.store'), ['email' => $portal->email, 'password' => $demo['portal']['password']])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($portal, 'portal');
    }

    public function test_demo_manager_changes_only_local_membership_and_preserves_peer_evidence(): void
    {
        Notification::fake();
        $demo = app(CreateSupportingModulesDemo::class)->execute();
        $manager = User::findOrFail($demo['lab_manager']['id']);
        $target = User::findOrFail($demo['peer_target']['id']);
        $qualification = PersonnelQualification::findOrFail($demo['peer_qualification_id']);
        $before = $target->getRawOriginal();
        $qualificationBefore = $qualification->getRawOriginal();
        $peerMembership = DB::table('lab_user')->where('user_id', $target->id)->sole();
        $this->assertSame($demo['peer_lab_id'], $peerMembership->lab_id);
        foreach ([$manager, $target] as $member) {
            $membership = DB::table('lab_user')->where('user_id', $member->id)->sole();
            $this->assertFalse($membership->can_view_network);
            $this->assertFalse($membership->can_manage_branding);
        }
        $this->actingAs($manager)->withSession(['active_lab_id' => $demo['main_lab_id']]);
        request()->setLaravelSession(app('session')->driver());
        $capabilities = app(StaffAccountAccess::class)->capabilities($manager, $target);
        foreach ($capabilities as $key => $allowed) {
            $this->assertSame(in_array($key, ['join', 'removeMembership'], true), $allowed, $key);
        }
        $this->post(route('users.membership.store'), ['email' => $target->email])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lab_user', ['lab_id' => $demo['main_lab_id'], 'user_id' => $target->id, 'can_view_network' => false, 'can_manage_branding' => false]);
        $this->delete(route('users.membership.destroy', $target))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('lab_user', ['lab_id' => $demo['main_lab_id'], 'user_id' => $target->id]);
        $this->assertSame($before, $target->fresh()->getRawOriginal());
        $this->assertEquals($peerMembership, DB::table('lab_user')->where('user_id', $target->id)->sole());
        $this->assertSame($qualificationBefore, $qualification->fresh()->getRawOriginal());
        $this->assertTrue($target->fresh()->roles->isEmpty());
        $this->assertTrue($target->fresh()->getAllPermissions()->isEmpty());
        Notification::assertNothingSent();
    }

    public function test_demo_qualification_editor_can_edit_only_local_evidence_for_a_shared_account(): void
    {
        Notification::fake();
        $demo = app(CreateSupportingModulesDemo::class)->execute();
        $editor = User::findOrFail($demo['qualification_editor']['id']);
        $target = User::findOrFail($demo['peer_target']['id']);
        $peer = PersonnelQualification::findOrFail($demo['peer_qualification_id']);
        $before = $target->getRawOriginal();
        $peerBefore = $peer->getRawOriginal();
        $this->actingAs(User::findOrFail($demo['lab_manager']['id']))->withSession(['active_lab_id' => $demo['main_lab_id']]);
        $this->post(route('users.membership.store'), ['email' => $target->email])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($editor);
        request()->setLaravelSession(app('session')->driver());
        $capabilities = app(StaffAccountAccess::class)->capabilities($editor, $target);
        foreach ($capabilities as $key => $allowed) {
            $this->assertSame($key === 'qualifications', $allowed, $key);
        }
        $this->put(route('users.update', $target), ['name' => 'Unauthorized name'])->assertForbidden();
        $this->put(route('users.update', $target), ['roles' => []])->assertForbidden();
        $this->put(route('users.update', $target), ['personnel_qualifications' => [[
            'capability' => 'Demonstration training only', 'is_active' => false,
        ]]])->assertRedirect()->assertSessionHasNoErrors();
        $local = $target->personnelQualifications()->where('lab_id', $demo['main_lab_id'])->sole();
        $this->assertFalse($local->is_active);
        $this->assertSame($editor->id, $local->qualified_by_id);
        $this->assertSame($before, $target->fresh()->getRawOriginal());
        $this->assertSame($peerBefore, $peer->fresh()->getRawOriginal());
        $this->assertTrue($target->fresh()->roles->isEmpty());
        $this->assertTrue($target->fresh()->getAllPermissions()->isEmpty());
        $this->assertSame(2, DB::table('lab_user')->where('user_id', $target->id)->count());
        $membership = DB::table('lab_user')->where('user_id', $editor->id)->sole();
        $this->assertSame($demo['main_lab_id'], $membership->lab_id);
        $this->assertFalse($membership->can_view_network);
        $this->assertFalse($membership->can_manage_branding);
        Notification::assertNothingSent();
    }

    public function test_repeated_setup_creates_distinct_fresh_datasets_instead_of_resetting_passwords(): void
    {
        Notification::fake();
        $action = app(CreateSupportingModulesDemo::class);
        $first = $action->execute();
        $passwordHash = User::findOrFail($first['staff']['id'])->password;
        $managerBefore = User::findOrFail($first['lab_manager']['id'])->getRawOriginal();
        $editorBefore = User::findOrFail($first['qualification_editor']['id'])->getRawOriginal();
        $second = $action->execute();
        $this->assertNotSame($first['marker'], $second['marker']);
        $this->assertNotSame($first['staff']['id'], $second['staff']['id']);
        $this->assertNotSame($first['main_lab_id'], $second['main_lab_id']);
        $this->assertNotSame($first['lab_manager']['id'], $second['lab_manager']['id']);
        $this->assertNotSame($first['peer_target']['id'], $second['peer_target']['id']);
        $this->assertNotSame($first['qualification_editor']['id'], $second['qualification_editor']['id']);
        $this->assertSame($passwordHash, User::findOrFail($first['staff']['id'])->password);
        $this->assertSame($managerBefore, User::findOrFail($first['lab_manager']['id'])->getRawOriginal());
        $this->assertSame($editorBefore, User::findOrFail($first['qualification_editor']['id'])->getRawOriginal());
        Notification::assertNothingSent();
    }

    public function test_demo_creation_is_forbidden_outside_local_or_testing(): void
    {
        $before = User::count();
        $this->app->instance('env', 'production');
        try {
            app(CreateSupportingModulesDemo::class)->execute();
            $this->fail('Production demo creation must be rejected.');
        } catch (LogicException) {
            $this->assertSame($before, User::count());
        } finally {
            $this->app->instance('env', 'testing');
        }
    }
}
