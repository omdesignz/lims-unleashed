<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Permission;
use App\Models\PersonnelQualification;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class UserAccountLaboratoryBoundaryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_creation_requires_a_real_password_and_joins_the_active_laboratory(): void
    {
        $lab = VAPLab::factory()->create();
        $admin = $this->member($lab, true);
        $this->actingAs($admin)->withSession(['active_lab_id' => $lab->id]);

        $payload = [
            'name' => 'New Laboratory Analyst',
            'email' => fake()->unique()->safeEmail(),
            'gender' => 'F',
            'departments' => [],
        ];

        $this->post(route('users.store'), $payload)->assertSessionHasErrors('password');
        $this->post(route('users.store'), [...$payload, 'password' => 'password', 'password_confirmation' => 'password'])
            ->assertSessionHasErrors('password');
        $this->post(route('users.store'), [...$payload, 'password' => 'Str0ng!Value123', 'password_confirmation' => 'Different1!'])
            ->assertSessionHasErrors('password');

        $this->post(route('users.store'), [...$payload, 'password' => 'Str0ng!Value123', 'password_confirmation' => 'Str0ng!Value123'])
            ->assertSessionHasNoErrors();

        $created = User::query()->where('email', $payload['email'])->firstOrFail();
        $this->assertTrue(Hash::check('Str0ng!Value123', $created->password));
        $this->assertFalse(Hash::check('password', $created->password));
        $this->assertTrue(DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $created->id)->exists());
    }

    public function test_user_list_and_lookup_expose_only_local_members_and_lookup_never_returns_secrets(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $admin = $this->member($lab, true);
        $local = $this->member($lab, false, 'Local Analyst');
        $foreign = $this->member($peer, false, 'Peer Analyst');
        $this->actingAs($admin)->withSession(['active_lab_id' => $lab->id]);

        $index = $this->get(route('users.index'));
        $index->assertOk();
        $visibleIds = collect(data_get($index->viewData('page'), 'props.record.data'))->pluck('id');
        $this->assertContains($local->id, $visibleIds);
        $this->assertNotContains($foreign->id, $visibleIds);

        $lookup = $this->get(route('users.getUser', ['q' => 'Analyst']));
        $lookup->assertOk()->assertExactJson([['id' => $local->id, 'name' => $local->name]]);
        $lookup->assertDontSee($local->email)->assertDontSee($local->password);

        $this->get(route('users.edit', $foreign))->assertNotFound();
        $this->put(route('users.update', $foreign), [
            'name' => $foreign->name,
            'email' => $foreign->email,
            'gender' => 'M',
            'departments' => [],
            'roles' => [],
            'permissions' => [],
        ])->assertNotFound();
    }

    public function test_password_reset_requires_permission_and_direct_target_membership(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $admin = $this->member($lab, true);
        $ordinary = $this->member($lab);
        $foreign = $this->member($peer);
        $payload = ['password' => 'NewStr0ng!Value123', 'password_confirmation' => 'NewStr0ng!Value123'];

        $this->actingAs($ordinary)->withSession(['active_lab_id' => $lab->id])
            ->put(route('users.setpass', $admin), $payload)->assertForbidden();

        $this->actingAs($admin)->withSession(['active_lab_id' => $lab->id])
            ->put(route('users.setpass', $foreign), $payload)->assertNotFound();
        $this->put(route('users.setpass', $ordinary), $payload)->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check($payload['password'], $ordinary->fresh()->password));
        $this->assertFalse(Hash::check($payload['password'], $foreign->fresh()->password));
    }

    public function test_bulk_archive_is_atomic_across_laboratories_and_uses_post(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $admin = $this->member($lab, true);
        $local = $this->member($lab);
        $foreign = $this->member($peer);
        $this->actingAs($admin)->withSession(['active_lab_id' => $lab->id]);

        $this->get(route('users.destroy'))->assertStatus(405);
        $this->post(route('users.destroy'), ['recordIds' => [$local->id, $foreign->id]])->assertNotFound();
        $this->assertNotSoftDeleted($local);
        $this->assertNotSoftDeleted($foreign);

        $this->post(route('users.destroy'), ['recordIds' => [$local->id]])->assertSessionHasNoErrors();
        $this->assertSoftDeleted($local);

        $this->post(route('users.restore'), ['recordIds' => [$local->id, $foreign->id]])->assertNotFound();
        $this->assertSoftDeleted($local);
        $this->post(route('users.restore'), ['recordIds' => [$local->id]])->assertSessionHasNoErrors();
        $this->assertNotSoftDeleted($local);
    }

    public function test_status_and_impersonation_are_permissioned_lab_local_post_actions(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $admin = $this->member($lab, true);
        $local = $this->member($lab);
        $foreign = $this->member($peer);

        $this->actingAs($local)->withSession(['active_lab_id' => $lab->id]);
        $this->post(route('users.setActiveStatus', $admin), ['is_active' => false])->assertForbidden();
        $this->post(route('users.impersonate'), ['id' => $admin->id])->assertForbidden();

        $this->actingAs($admin)->withSession(['active_lab_id' => $lab->id]);
        $this->get(route('users.setActiveStatus', $local))->assertStatus(405);
        $this->get(route('users.impersonate', ['id' => $local->id]))->assertStatus(405);
        $this->post(route('users.setActiveStatus', $foreign), ['is_active' => false])->assertNotFound();
        $this->post(route('users.impersonate'), ['id' => $foreign->id])->assertNotFound();
        $this->post(route('users.setActiveStatus', $admin), ['is_active' => false])->assertForbidden();

        $this->post(route('users.impersonate'), ['id' => $local->id])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($local);
        $this->post(route('users.stopimpersonating'))->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);

        $this->post(route('users.setActiveStatus', $local), ['is_active' => false])->assertSessionHasNoErrors();
        $this->assertFalse($local->fresh()->is_active);
    }

    public function test_signature_removal_is_not_a_get_mutation(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->member($lab);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        $this->get(route('users.unsetsignature'))->assertStatus(405);
        $this->delete(route('users.unsetsignature'))->assertSessionHasNoErrors();
    }

    public function test_profile_editor_cannot_escalate_global_roles_or_permissions(): void
    {
        $lab = VAPLab::factory()->create();
        $editor = $this->member($lab);
        $editor->givePermissionTo(Permission::findOrCreate('edit_users', 'web'));
        $target = $this->member($lab, false, 'Laboratory Analyst');
        $analystRole = Role::findOrCreate('laboratory_analyst', 'web');
        $target->assignRole($analystRole);
        $adminRole = Role::findOrCreate('admin', 'web');
        $privilegedPermission = Permission::findOrCreate('delete_users', 'web');
        $this->actingAs($editor)->withSession(['active_lab_id' => $lab->id]);

        $payload = [
            'name' => 'Updated Laboratory Analyst',
            'email' => $target->email,
            'gender' => 'O',
            'departments' => [],
        ];
        $this->put(route('users.update', $target), ['personnel_qualifications' => []])->assertSessionHasNoErrors();
        $this->put(route('users.update', $target), $payload)->assertForbidden();
        $this->assertSame('Laboratory Analyst', $target->fresh()->name);
        $this->assertTrue($target->fresh()->hasRole($analystRole));

        $this->put(route('users.update', $target), [
            ...$payload,
            'roles' => [['value' => $adminRole->id]],
        ])->assertForbidden();
        $this->put(route('users.update', $target), [
            ...$payload,
            'roles' => [['value' => $analystRole->id]],
            'permissions' => [['value' => $privilegedPermission->id]],
        ])->assertForbidden();

        $this->assertTrue($target->fresh()->hasRole($analystRole));
        $this->assertFalse($target->fresh()->hasRole($adminRole));
        $this->assertFalse($target->fresh()->hasDirectPermission($privilegedPermission));
    }

    public function test_lab_editor_cannot_change_shared_account_even_with_global_action_permissions(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $editor = $this->member($lab);
        $target = $this->member($lab);
        DB::table('lab_user')->insert(['lab_id' => $peer->id, 'user_id' => $target->id]);
        foreach (['view_users', 'edit_users', 'add_users', 'delete_users', 'restore_users', 'ban_users', 'reset-password_users', 'impersonate_users', 'edit_roles', 'edit_permissions'] as $permission) {
            $editor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $before = $target->fresh()->getAttributes();
        $this->actingAs($editor)->withSession(['active_lab_id' => $lab->id]);
        foreach (['name' => 'Changed Global Name', 'email' => fake()->safeEmail(), 'username' => 'changed_user', 'gender' => 'O',
            'dob' => '2001-01-01', 'id_number' => '123456789', 'primary_phone' => '123456789', 'secondary_phone' => '987654321',
            'departments' => [], 'roles' => [], 'permissions' => []] as $field => $value) {
            $this->put(route('users.update', $target), [$field => $value])->assertForbidden();
        }
        $this->post(route('users.store'), ['name' => 'New Shared Analyst'])->assertForbidden();
        $this->get(route('users.create'))->assertForbidden();
        $this->put(route('users.setpass', $target), ['password' => 'NewStr0ng!Value123', 'password_confirmation' => 'NewStr0ng!Value123'])->assertForbidden();
        $this->post(route('users.setActiveStatus', $target), ['is_active' => false])->assertForbidden();
        $this->post(route('users.destroy'), ['recordIds' => [$target->id]])->assertForbidden();
        $this->post(route('users.restore'), ['recordIds' => [$target->id]])->assertForbidden();
        $this->post(route('users.impersonate'), ['id' => $target->id])->assertForbidden();
        $this->assertSame($before, $target->fresh()->getAttributes());
        $this->assertSame(2, DB::table('lab_user')->where('user_id', $target->id)->count());
        $index = $this->get(route('users.index'))->assertOk();
        $this->assertFalse(data_get($index->viewData('page'), 'props.accountCapabilities.create'));
        $edit = $this->get(route('users.edit', $target))->assertOk();
        $this->assertFalse(data_get($edit->viewData('page'), 'props.accountCapabilities.profile'));
        $this->assertTrue(data_get($edit->viewData('page'), 'props.accountCapabilities.qualifications'));
    }

    public function test_local_qualifications_preserve_shared_identity_access_and_peer_evidence(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $editor = $this->member($lab);
        $editor->givePermissionTo(Permission::findOrCreate('edit_users', 'web'));
        $target = $this->member($lab);
        DB::table('lab_user')->insert(['lab_id' => $peer->id, 'user_id' => $target->id]);
        $peerQualification = PersonnelQualification::query()->create(['lab_id' => $peer->id, 'user_id' => $target->id, 'capability' => 'Peer verification', 'is_active' => true]);
        $before = $target->fresh()->getAttributes();
        $peerBefore = $peerQualification->fresh()->getAttributes();
        $this->actingAs($editor)->withSession(['active_lab_id' => $lab->id]);
        $this->put(route('users.update', $target), ['personnel_qualifications' => [['capability' => 'Local verification', 'is_active' => false,
            'authorized_from' => '2026-01-01', 'authorized_until' => '2026-12-31', 'training_reference' => 'LOCAL-1']]])->assertSessionHasNoErrors();
        $qualification = $target->personnelQualifications()->where('lab_id', $lab->id)->firstOrFail();
        $this->assertFalse($qualification->is_active);
        $this->assertSame($editor->id, $qualification->qualified_by_id);
        $this->assertSame('Local verification', $qualification->capability);
        $this->assertSame($before, $target->fresh()->getAttributes());
        $this->assertSame($peerBefore, $peerQualification->fresh()->getAttributes());
        $this->put(route('users.update', $target), ['personnel_qualifications' => [['capability' => 'Invalid boolean', 'is_active' => 'false']]])->assertSessionHasErrors('personnel_qualifications.0.is_active');
        $this->assertSame('Local verification', $qualification->fresh()->capability);
        $this->put(route('users.update', $target), ['personnel_qualifications' => []])->assertSessionHasNoErrors();
        $this->assertFalse($target->personnelQualifications()->where('lab_id', $lab->id)->exists());
        $this->assertModelExists($peerQualification);
        $department = Department::factory()->create();
        $this->put(route('users.update', $target), ['personnel_qualifications' => [['capability' => 'String department identifier',
            'department_id' => ['value' => (string) $department->id], 'is_active' => null]]])->assertSessionHasNoErrors();
        $qualification = $target->personnelQualifications()->where('lab_id', $lab->id)->firstOrFail();
        $this->assertSame($department->id, $qualification->department_id);
        $this->assertTrue($qualification->is_active);
    }

    public function test_owner_can_edit_profile_but_cannot_assign_global_access(): void
    {
        $lab = VAPLab::factory()->create();
        $owner = $this->member($lab);
        foreach (['edit_users', 'edit_roles', 'edit_permissions'] as $permission) {
            $owner->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($owner)->withSession(['active_lab_id' => $lab->id]);
        $this->put(route('users.update', $owner), ['name' => 'Updated Owner Name', 'dob' => '2000-01-02'])->assertSessionHasNoErrors();
        $this->assertSame('Updated Owner Name', $owner->fresh()->name);
        $this->assertSame('2000-01-02', $owner->fresh()->dob->format('Y-m-d'));
        $this->put(route('users.update', $owner), ['roles' => [Role::findOrCreate('admin', 'web')->id]])->assertForbidden();
        $this->put(route('users.update', $owner), ['permissions' => []])->assertForbidden();
        $this->put(route('users.update', $owner), ['departments' => []])->assertForbidden();
        $this->assertFalse($owner->fresh()->hasRole('admin'));
        $email = fake()->unique()->safeEmail();
        $this->put(route('users.update', $owner), ['email' => $email])->assertSessionHasNoErrors();
        $this->assertSame($email, $owner->fresh()->email);
        $this->assertNull($owner->fresh()->email_verified_at);
    }

    public function test_self_email_and_qualification_changes_roll_back_when_the_laboratory_is_archived(): void
    {
        $lab = VAPLab::factory()->create();
        $owner = $this->member($lab, true);
        $owner->forceFill(['last_activity_at' => now()])->save();
        $before = $owner->fresh()->getAttributes();
        PersonnelQualification::creating(function () use ($lab): void {
            VAPLab::query()->whereKey($lab->id)->delete();
        });
        $this->actingAs($owner)->withSession(['active_lab_id' => $lab->id]);
        $this->put(route('users.update', $owner), ['email' => fake()->unique()->safeEmail(),
            'personnel_qualifications' => [['capability' => 'New qualification', 'is_active' => true]]])->assertNotFound();
        $this->assertSame($before, $owner->fresh()->getAttributes());
        $this->assertFalse($owner->personnelQualifications()->exists());
        $this->assertModelExists($lab);
        $this->assertNull($lab->fresh()->deleted_at);
    }

    public function test_catalog_and_impersonation_permissions_cannot_create_a_system_administrator(): void
    {
        $lab = VAPLab::factory()->create();
        $editor = $this->member($lab);
        $admin = $this->member($lab, true);
        $role = Role::findOrCreate('restricted_catalog_editor', 'web');
        $editor->assignRole($role);
        foreach (['roles', 'permissions'] as $catalogue) {
            foreach (['view', 'add', 'edit', 'delete', 'restore'] as $verb) {
                $editor->givePermissionTo(Permission::findOrCreate($verb.'_'.$catalogue, 'web'));
            }
        }
        $permission = Permission::findOrCreate('harmless_example', 'web');
        $editor->givePermissionTo($permission);
        $editor->givePermissionTo(Permission::findOrCreate('impersonate_users', 'web'));
        $this->actingAs($editor)->withSession(['active_lab_id' => $lab->id]);
        foreach (['roles' => $role, 'permissions' => $permission] as $catalogue => $record) {
            $this->post(route($catalogue.'.store'), ['name' => 'forged_global'])->assertForbidden();
            $this->put(route($catalogue.'.update', $record), ['name' => 'admin'])->assertForbidden();
            $this->get(route($catalogue.'.create'))->assertForbidden();
            $this->get(route($catalogue.'.edit', $record))->assertForbidden();
            $this->get(route($catalogue.'.destroy'), ['recordIds' => [$record->id]])->assertStatus(405);
            $this->get(route($catalogue.'.restore'), ['recordIds' => [$record->id]])->assertStatus(405);
            $this->post(route($catalogue.'.destroy'), ['recordIds' => [$record->id]])->assertForbidden();
            $this->post(route($catalogue.'.restore'), ['recordIds' => [$record->id]])->assertForbidden();
        }
        $this->post(route('users.impersonate'), ['id' => $admin->id])->assertForbidden();
        $this->assertAuthenticatedAs($editor);
        $this->assertSame('restricted_catalog_editor', $role->fresh()->name);
        $this->assertSame('harmless_example', $permission->fresh()->name);
        $this->assertFalse($editor->fresh()->hasRole('admin'));
    }

    #[DataProvider('accountPersistenceFaults')]
    public function test_account_and_qualification_mutations_fail_atomically_on_lifecycle_faults(string $fault): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $admin = $this->member($lab, true);
        $target = $this->member($lab);
        DB::table('lab_user')->insert(['lab_id' => $peer->id, 'user_id' => $target->id]);
        $peerQualification = PersonnelQualification::query()->create(['lab_id' => $peer->id, 'user_id' => $target->id, 'capability' => 'Peer verification', 'is_active' => true]);
        PersonnelQualification::query()->create(['lab_id' => $lab->id, 'user_id' => $target->id, 'capability' => 'Old local verification', 'is_active' => true]);
        $before = [];
        foreach (['users', 'personnel_qualifications', 'lab_user', 'model_has_roles', 'model_has_permissions', 'department_user'] as $table) {
            $before[$table] = DB::table($table)->get()->toArray();
        }
        if (in_array($fault, ['veto_user', 'corrupt_user'], true)) {
            User::saving(function (User $user) use ($fault, $target): ?bool {
                if ($user->id !== $target->id) {
                    return null;
                }
                if ($fault === 'veto_user') {
                    return false;
                }
                $user->email = 'forged@example.test';

                return null;
            });
        } else {
            PersonnelQualification::creating(function (PersonnelQualification $qualification) use ($fault, $peerQualification, $target, $admin, $lab): ?bool {
                if ($fault === 'veto_qualification') {
                    return false;
                }
                if ($fault === 'corrupt_qualification') {
                    $qualification->lab_id = $peerQualification->lab_id;
                } elseif ($fault === 'peer_evidence') {
                    DB::table('personnel_qualifications')->where('id', $peerQualification->id)->update(['capability' => 'Changed peer evidence']);
                } elseif ($fault === 'extra_qualification') {
                    DB::table('personnel_qualifications')->insert(['lab_id' => $lab->id, 'user_id' => $target->id, 'capability' => 'Injected evidence', 'is_active' => true]);
                } elseif ($fault === 'actor_role') {
                    DB::table('model_has_roles')->where('model_id', $admin->id)->where('model_type', $admin->getMorphClass())->delete();
                } elseif ($fault === 'actor_member') {
                    DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $admin->id)->delete();
                } elseif ($fault === 'actor_inactive') {
                    DB::table('users')->where('id', $admin->id)->update(['is_active' => false]);
                } elseif ($fault === 'target_member') {
                    DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $target->id)->delete();
                } elseif ($fault === 'global_pivot') {
                    DB::table('model_has_roles')->insert(['role_id' => Role::findOrCreate('admin', 'web')->id, 'model_id' => $target->id, 'model_type' => $target->getMorphClass()]);
                }

                return null;
            });
        }
        $this->actingAs($admin)->withSession(['active_lab_id' => $lab->id]);
        $this->withoutExceptionHandling();
        try {
            $this->put(route('users.update', $target), ['name' => 'Updated Global Analyst', 'personnel_qualifications' => [['capability' => 'New local qualification', 'is_active' => true]]]);
            $this->fail('Expected account mutation to fail closed.');
        } catch (\LogicException|AuthorizationException|HttpException $exception) {
            if ($exception instanceof \LogicException) {
                $this->assertContains($exception->getMessage(), ['Staff account changes were not persisted.', 'Staff qualification changes were not persisted.',
                    'Persisted staff account differs from the intended account.', 'Persisted staff access differs from the intended access.', 'Persisted staff qualifications differ from the intended qualifications.']);
            } else {
                $this->addToAssertionCount(1);
            }
        }
        foreach ($before as $table => $rows) {
            $this->assertEqualsCanonicalizing($rows, DB::table($table)->get()->toArray(), $table.' changed on failure.');
        }
    }

    /** @return array<string,array{string}> */
    public static function accountPersistenceFaults(): array
    {
        return array_combine(['veto_user', 'corrupt_user', 'veto_qualification', 'corrupt_qualification', 'peer_evidence', 'extra_qualification',
            'actor_role', 'actor_member', 'actor_inactive', 'target_member', 'global_pivot'], array_map(fn (string $fault): array => [$fault],
                ['veto_user', 'corrupt_user', 'veto_qualification', 'corrupt_qualification', 'peer_evidence', 'extra_qualification',
                    'actor_role', 'actor_member', 'actor_inactive', 'target_member', 'global_pivot']));
    }

    public function test_dossier_rejects_credential_status_and_server_identity_fields(): void
    {
        $lab = VAPLab::factory()->create();
        $admin = $this->member($lab, true);
        $target = $this->member($lab);
        $before = $target->fresh()->getAttributes();
        $this->actingAs($admin)->withSession(['active_lab_id' => $lab->id]);
        foreach (['password' => 'ForgedPassw0rd!', 'is_active' => false, 'email_verified_at' => now()->toDateTimeString(), 'id' => $admin->id] as $field => $value) {
            $this->put(route('users.update', $target), [$field => $value])->assertSessionHasErrors($field);
        }
        $this->assertSame($before, $target->fresh()->getAttributes());
    }

    private function member(VAPLab $lab, bool $admin = false, ?string $name = null): User
    {
        $user = User::factory()->create([
            'name' => $name ?? fake()->name(),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        if ($admin) {
            $user->assignRole(Role::findOrCreate('admin', 'web'));
        }
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        return $user;
    }
}
