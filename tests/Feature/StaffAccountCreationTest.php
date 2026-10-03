<?php

namespace Tests\Feature;

use App\Actions\CreateStaffAccount;
use App\Models\Department;
use App\Models\ISOActivityLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class StaffAccountCreationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_creation_preserves_defaults_and_records_private_account_and_local_membership_history(): void
    {
        [$lab, $peer, $actor, $department, $other] = $this->fixture();
        $department->delete();
        Event::fake([Registered::class]);
        Mail::fake();
        Notification::fake();
        $payload = $this->payload();
        $payload['name'] = 'New :subject.email analyst';
        $payload['departments'] = [['department_id' => $other->id], ['department_id' => $department->id], ['department_id' => $department->id]];
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->post(route('users.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $target = User::where('email', $payload['email'])->sole();
        $this->assertTrue(Hash::check($payload['password'], $target->password));
        $this->assertTrue($target->is_active);
        $this->assertFalse($target->password_changed_by_user);
        $this->assertSame('light', $target->theme);
        foreach (['email_verified_at', 'password_changed_at', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
            'google_id', 'github_id', 'microsoft_id', 'x_id', 'microsoft_data', 'profile_photo_path', 'dob', 'birthday', 'id_number', 'primary_phone', 'secondary_phone', 'last_login_at', 'last_activity_at', 'deleted_at'] as $field) {
            $this->assertNull($target->getAttributes()[$field], $field);
        }
        $membership = DB::table('lab_user')->where('user_id', $target->id)->sole();
        $this->assertSame($lab->id, $membership->lab_id);
        $this->assertFalse($membership->can_view_network);
        $this->assertFalse($membership->can_manage_branding);
        $this->assertNotNull($membership->created_at);
        $this->assertDatabaseMissing('lab_user', ['user_id' => $target->id, 'lab_id' => $peer->id]);
        $this->assertSame([$department->id, $other->id], DB::table('department_user')->where('user_id', $target->id)->orderBy('department_id')->pluck('department_id')->all());
        $this->assertSame(0, $target->roles()->count());
        $this->assertSame(0, $target->permissions()->count());
        $this->assertSame(0, $target->personnelQualifications()->count());
        $this->assertSame(0, $target->tokens()->count());
        $this->assertSame(0, $target->passkeys()->count());
        $this->assertSame(0, $target->media()->count());
        $global = ISOActivityLog::withoutGlobalScopes()->where('log_name', 'staff_account')->sole();
        $local = ISOActivityLog::withoutGlobalScopes()->where('log_name', 'laboratory_membership')->sole();
        $this->assertSame('created', $global->event);
        $this->assertSame('criou uma conta partilhada', $global->description);
        $this->assertSame($target->id, (int) $global->subject_id);
        $this->assertSame($actor->id, (int) $global->causer_id);
        $this->assertSame($lab->id, $global->properties['origin_lab_id']);
        $this->assertSame($payload['name'], $global->properties['attributes']['name']);
        $this->assertSame([$department->id, $other->id], $global->properties['attributes']['departments']);
        $this->assertFalse($global->properties['attributes']['email_verified']);
        $this->assertSame('membership_added', $local->event);
        $this->assertSame($lab->id, (int) $local->subject_id);
        $this->assertSame(['joined' => true, 'lab_id' => $lab->id, 'target_user_id' => $target->id], $local->properties->sortKeys()->all());
        foreach ([$global, $local] as $audit) {
            $this->assertStringNotContainsString($payload['password'], $audit->toJson());
            $this->assertStringNotContainsString($target->password, $audit->toJson());
            $this->getJson(route('systemactivity.show', $audit))->assertOk()->assertJsonPath('activity.is_retained', true);
        }
        $this->post(route('users.store'), $payload)->assertSessionHasErrors('email');
        $this->assertSame(2, ISOActivityLog::withoutGlobalScopes()->where('properties->target_user_id', $target->id)->count());
        Event::assertNotDispatched(Registered::class);
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
        Notification::assertNothingSent();
    }

    public function test_request_uses_schema_types_lengths_and_rejects_global_security_fields(): void
    {
        [$lab, , $actor] = $this->fixture();
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        foreach (['name' => ['invalid'], 'username' => ['invalid'], 'gender' => 'invalid', 'email' => str_repeat('a', 256).'@example.test'] as $field => $value) {
            $this->post(route('users.store'), [...$this->payload(), $field => $value])->assertSessionHasErrors($field);
        }
        foreach (['name', 'username'] as $field) {
            $this->post(route('users.store'), [...$this->payload(), $field => str_repeat('a', 256)])->assertSessionHasErrors($field);
        }
        foreach (['is_active' => false, 'email_verified_at' => now()->toDateTimeString(), 'roles' => [], 'permissions' => [], 'lab_id' => $lab->id,
            'two_factor_secret' => 'forged', 'password_changed_by_user' => true, 'theme' => 'dark'] as $field => $value) {
            $this->post(route('users.store'), [...$this->payload(), $field => $value])->assertSessionHasErrors($field);
        }
        $this->post(route('users.store'), [...$this->payload(), 'departments' => [['department_id' => 999999999]]])->assertSessionHasErrors('departments.0.department_id');
        $this->assertSame(0, ISOActivityLog::withoutGlobalScopes()->where('log_name', 'staff_account')->count());
    }

    public function test_archived_identity_is_not_recreated_or_implicitly_joined(): void
    {
        [$lab, $peer, $actor] = $this->fixture();
        $existing = User::factory()->create(['username' => 'existing-account']);
        DB::table('lab_user')->insert(['lab_id' => $peer->id, 'user_id' => $existing->id]);
        $existing->delete();
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->post(route('users.store'), [...$this->payload(), 'email' => $existing->email])->assertSessionHasErrors('email');
        $this->post(route('users.store'), [...$this->payload(), 'username' => $existing->username])->assertSessionHasErrors('username');
        $this->assertSoftDeleted($existing);
        $this->assertDatabaseMissing('lab_user', ['lab_id' => $lab->id, 'user_id' => $existing->id]);
    }

    public function test_existing_identifiers_raise_controlled_validation_at_the_action_boundary(): void
    {
        [$lab, , $actor] = $this->fixture();
        $existing = User::factory()->create(['username' => 'existing-account']);
        request()->setLaravelSession(app('session')->driver());
        foreach (['email' => $existing->email, 'username' => $existing->username] as $field => $value) {
            try {
                app(CreateStaffAccount::class)->execute($actor->id, $lab->id, [...$this->payload(), 'name' => 'Analyst "users_email_unique" "users_username_unique"', $field => $value]);
                $this->fail('Expected controlled duplicate identity validation.');
            } catch (ValidationException $exception) {
                $this->assertSame([$field], array_keys($exception->errors()));
            }
        }
        $this->assertSame(0, ISOActivityLog::withoutGlobalScopes()->where('log_name', 'staff_account')->count());
    }

    public function test_lab_staff_with_creation_permission_cannot_create_a_shared_account(): void
    {
        $this->freezeTime();
        [$lab, , $actor] = $this->fixture();
        $actor->removeRole('admin');
        $actor->givePermissionTo('add_users');
        $actor->forceFill(['last_activity_at' => now()])->save();
        $before = $this->snapshot();
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->post(route('users.store'), $this->payload())->assertForbidden();
        $this->assertEquals($before, $this->snapshot());
        request()->setLaravelSession(app('session')->driver());
        try {
            app(CreateStaffAccount::class)->execute($actor->id, $lab->id, $this->payload());
            $this->fail('Expected system-administrator authorization.');
        } catch (AuthorizationException) {
            $this->assertEquals($before, $this->snapshot());
        }
    }

    public function test_unrelated_unique_constraints_are_not_disguised_as_identity_validation(): void
    {
        [$lab, , $actor] = $this->fixture();
        $before = $this->snapshot();
        ISOActivityLog::creating(function (ISOActivityLog $audit) use ($lab): void {
            if ($audit->log_name === 'staff_account') {
                DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $audit->properties['target_user_id']]);
            }
        });
        request()->setLaravelSession(app('session')->driver());
        try {
            app(CreateStaffAccount::class)->execute($actor->id, $lab->id,
                [...$this->payload(), 'name' => 'Analyst "users_email_unique" "users_username_unique"']);
            $this->fail('Expected the original unrelated database constraint failure.');
        } catch (UniqueConstraintViolationException $exception) {
            $this->assertSame('lab_user_lab_id_user_id_unique', $exception->index);
        }
        $this->assertEquals($before, $this->snapshot());
    }

    #[DataProvider('creationFaults')]
    public function test_lifecycle_and_audit_faults_rollback_the_complete_new_account_graph(string $fault): void
    {
        [$lab, $peer, $actor, $department, $other] = $this->fixture();
        $payload = $this->payload();
        $payload['departments'] = [['department_id' => $department->id]];
        $before = $this->snapshot();
        User::creating(function (User $target) use ($fault, $payload): ?bool {
            if ($target->email !== $payload['email']) {
                return null;
            }
            if ($fault === 'user_veto') {
                return false;
            }
            if ($fault === 'user_intent') {
                $target->name = 'FORGED-CREATING';
            }

            return null;
        });
        if ($fault === 'disabled') {
            activity()->disableLogging();
        }
        ISOActivityLog::creating(function (ISOActivityLog $audit) use ($fault, $lab, $peer, $actor, $other): ?bool {
            if (! in_array($audit->log_name, ['staff_account', 'laboratory_membership'], true)) {
                return null;
            }
            if ($fault === 'audit_veto') {
                return false;
            }
            if ($audit->log_name !== 'laboratory_membership') {
                return null;
            }
            $id = $audit->properties['target_user_id'];
            $target = User::withTrashed()->findOrFail($id);
            match ($fault) {
                'description' => $audit->description = 'FORGED-AUDIT',
                'properties' => $audit->properties = collect(['target_user_id' => $id, 'joined' => false]),
                'identity' => $audit->causer_id = $id,
                'first_audit' => DB::table('activity_log')->where('log_name', 'staff_account')->where('subject_id', $id)->update(['description' => 'FORGED-FIRST']),
                'extra_audit' => DB::table('activity_log')->insert(['log_name' => 'staff_account', 'description' => 'FORGED-EXTRA', 'properties' => json_encode(['target_user_id' => $id]), 'created_at' => now(), 'updated_at' => now()]),
                'root' => DB::table('users')->where('id', $id)->update(['name' => 'FORGED-ROOT']),
                'timestamp' => DB::table('users')->where('id', $id)->update(['created_at' => now()->subYear()]),
                'password' => DB::table('users')->where('id', $id)->update(['password' => Hash::make('FORGED')]),
                'verified' => DB::table('users')->where('id', $id)->update(['email_verified_at' => now()]),
                'inactive' => DB::table('users')->where('id', $id)->update(['is_active' => false]),
                'secret' => DB::table('users')->where('id', $id)->update(['two_factor_secret' => 'FORGED-SECRET']),
                'archived' => DB::table('users')->where('id', $id)->update(['deleted_at' => now()]),
                'membership' => DB::table('lab_user')->where('user_id', $id)->delete(),
                'membership_flags' => DB::table('lab_user')->where('user_id', $id)->update(['can_view_network' => true]),
                'peer_membership' => DB::table('lab_user')->insert(['user_id' => $id, 'lab_id' => $peer->id]),
                'department' => DB::table('department_user')->where('user_id', $id)->update(['department_id' => $other->id]),
                'department_pivot' => DB::table('department_user')->where('user_id', $id)->update(['deleted_at' => now()]),
                'role' => $target->assignRole(Role::findOrCreate('admin', 'web')),
                'permission' => $target->givePermissionTo('add_users'),
                'qualification' => DB::table('personnel_qualifications')->insert(['lab_id' => $lab->id, 'user_id' => $id, 'capability' => 'FORGED', 'is_active' => true]),
                'token' => $target->createToken('FORGED-TOKEN'),
                'passkey', 'passkey_alias' => DB::table('passkeys')->insert(['authenticatable_id' => $id, 'authenticatable_type' => $fault === 'passkey' ? User::class : $target->getMorphClass(),
                    'name' => 'FORGED', 'credential_id' => fake()->uuid(), 'data' => '{}', 'created_at' => now(), 'updated_at' => now()]),
                'actor_role' => DB::table('model_has_roles')->where('model_id', $actor->id)->delete(),
                'actor_membership' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $actor->id)->delete(),
                'actor_active' => DB::table('users')->where('id', $actor->id)->update(['is_active' => false]),
                'actor_verified' => DB::table('users')->where('id', $actor->id)->update(['email_verified_at' => null]),
                'lab_archived' => DB::table('labs')->where('id', $lab->id)->update(['deleted_at' => now()]),
                'impersonation' => request()->session()->put('impersonate', $actor->id),
                default => null,
            };

            return null;
        });
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->withoutExceptionHandling();
        try {
            $this->post(route('users.store'), $payload);
            $this->fail('Expected creation to fail closed.');
        } catch (\LogicException|AuthorizationException|HttpException $exception) {
            $this->addToAssertionCount(1);
        } finally {
            activity()->enableLogging();
        }
        $this->assertEquals($before, $this->snapshot());
    }

    /** @return array<string,array{string}> */
    public static function creationFaults(): array
    {
        $faults = ['user_veto', 'user_intent', 'disabled', 'audit_veto', 'description', 'properties', 'identity', 'first_audit', 'extra_audit', 'root', 'timestamp', 'password', 'verified',
            'inactive', 'secret', 'archived', 'membership', 'membership_flags', 'peer_membership', 'department', 'department_pivot', 'role', 'permission', 'qualification',
            'token', 'passkey', 'passkey_alias', 'actor_role', 'actor_membership', 'actor_active', 'actor_verified', 'lab_archived', 'impersonation'];

        return array_combine($faults, array_map(fn (string $fault): array => [$fault], $faults));
    }

    /** @return array{VAPLab,VAPLab,User,Department,Department} */
    private function fixture(): array
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $actor = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $actor->assignRole(Role::findOrCreate('admin', 'web'));
        foreach (['add_users', 'view_users', 'view_activity_log'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $actor->id]);

        return [$lab, $peer, $actor, Department::factory()->create(), Department::factory()->create()];
    }

    /** @return array{name:string,email:string,gender:string,password:string,password_confirmation:string} */
    private function payload(): array
    {
        return ['name' => 'New Laboratory Analyst', 'email' => fake()->unique()->safeEmail(), 'gender' => 'F',
            'password' => 'Str0ng!Value123', 'password_confirmation' => 'Str0ng!Value123'];
    }

    /** @return array<string,array<mixed>> */
    private function snapshot(): array
    {
        $before = [];
        foreach (['users', 'labs', 'lab_user', 'department_user', 'model_has_roles', 'model_has_permissions', 'personnel_qualifications', 'passkeys', 'personal_access_tokens', 'media', 'activity_log'] as $table) {
            $before[$table] = DB::table($table)->get()->toArray();
        }

        return $before;
    }
}
