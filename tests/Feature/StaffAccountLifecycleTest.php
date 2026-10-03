<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ISOActivityLog;
use App\Models\Permission;
use App\Models\PersonnelQualification;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class StaffAccountLifecycleTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('operations')]
    public function test_lifecycle_intent_is_audited_once_and_preserves_shared_evidence(string $operation): void
    {
        [$lab, , $actor, $target] = $this->fixture($operation);
        Storage::fake('public');
        $media = $target->addMedia(UploadedFile::fake()->create('evidence.pdf', 2, 'application/pdf'))->toMediaCollection('staff_evidence', 'public');
        $before = $target->fresh()->getAttributes();
        $graph = $this->snapshot(['lab_user', 'personnel_qualifications', 'department_user', 'model_has_roles', 'model_has_permissions', 'passkeys', 'personal_access_tokens', 'media']);
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->mutate($operation, $target)->assertRedirect()->assertSessionHasNoErrors();
        $after = User::withTrashed()->findOrFail($target->id)->getAttributes();
        unset($before['updated_at'], $after['updated_at']);
        if ($operation === 'archive') {
            $this->assertNotNull($after['deleted_at']);
            $before['deleted_at'] = $after['deleted_at'];
        } elseif ($operation === 'restore') {
            $before['deleted_at'] = null;
        } elseif (in_array($operation, ['activate', 'deactivate'], true)) {
            $before['is_active'] = $operation === 'activate';
        } else {
            $this->assertTrue(Hash::check('NewStr0ng!Value123', $after['password']));
            $this->assertNotSame($before['password'], $after['password']);
            $before['password'] = $after['password'];
        }
        $this->assertSame($before, $after);
        $this->assertEquals($graph, $this->snapshot(array_keys($graph)));
        Storage::disk('public')->assertExists($media->getPathRelativeToRoot());
        $audit = ISOActivityLog::withoutGlobalScopes()->where('log_name', 'staff_account')->sole();
        $this->assertSame(match ($operation) {
            'archive' => 'archived', 'restore' => 'restored', 'activate' => 'activated', 'deactivate' => 'deactivated', 'password' => 'password_reset',
        }, $audit->event);
        $this->assertSame($actor->id, (int) $audit->causer_id);
        $this->assertSame($target->id, (int) $audit->subject_id);
        $this->assertSame($lab->id, $audit->properties['origin_lab_id']);
        $this->assertSame($target->id, $audit->properties['target_user_id']);
        foreach (['NewStr0ng!Value123', $after['password'], '2FA-MARKER', 'RECOVERY-MARKER', 'OAUTH-MARKER', 'PASSKEY-MARKER', 'API-MARKER'] as $secret) {
            $this->assertStringNotContainsString($secret, $audit->toJson());
        }
        $this->mutate($operation, $target)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1, ISOActivityLog::withoutGlobalScopes()->where('log_name', 'staff_account')->count());
        $this->assertSame($after, array_diff_key(User::withTrashed()->findOrFail($target->id)->getAttributes(), ['updated_at' => true]));
        $this->assertEquals($graph, $this->snapshot(array_keys($graph)));
    }

    public function test_lifecycle_requests_reject_missing_intent_and_unrelated_fields(): void
    {
        [$lab, , $actor, $target] = $this->fixture('deactivate');
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        foreach ([[], ['is_active' => null], ['is_active' => 'false'], ['is_active' => []]] as $payload) {
            $this->post(route('users.setActiveStatus', $target), $payload)->assertSessionHasErrors('is_active');
        }
        $this->post(route('users.setActiveStatus', $target), ['is_active' => false, 'roles' => []])->assertSessionHasErrors('roles');
        $this->post(route('users.destroy'), ['recordIds' => [$target->id], 'lab_id' => $lab->id])->assertSessionHasErrors('lab_id');
        $this->post(route('users.restore'), ['recordIds' => [$target->id], 'is_active' => true])->assertSessionHasErrors('is_active');
        $this->put(route('users.setpass', $target), ['password' => 'NewStr0ng!Value123', 'password_confirmation' => 'NewStr0ng!Value123', 'remember_token' => 'forged'])
            ->assertSessionHasErrors('remember_token');
        foreach ([[], [$target->id, $target->id], [0], ['bad']] as $ids) {
            $this->post(route('users.destroy'), ['recordIds' => $ids])->assertSessionHasErrors();
        }
        $this->assertTrue($target->fresh()->is_active);
        $this->assertSame(0, ISOActivityLog::withoutGlobalScopes()->where('log_name', 'staff_account')->count());
    }

    public function test_archived_accounts_cannot_receive_status_or_password_changes(): void
    {
        [$lab, , $actor, $target] = $this->fixture('restore');
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->mutate('password', $target)->assertNotFound();
        $this->mutate('deactivate', $target)->assertNotFound();
    }

    #[DataProvider('operations')]
    public function test_late_lifecycle_veto_rolls_back_the_batch_or_single_change(string $operation): void
    {
        [$lab, , $actor, $target] = $this->fixture($operation);
        $second = User::factory()->create(['is_active' => $operation !== 'activate', 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $second->id]);
        if ($operation === 'restore') {
            $second->delete();
        }
        $before = $this->snapshot();
        $event = match ($operation) {
            'archive' => 'deleting', 'restore' => 'restoring', default => 'saving'
        };
        $vetoId = in_array($operation, ['archive', 'restore'], true) ? $second->id : $target->id;
        User::{$event}(fn (User $user): ?bool => $user->id === $vetoId ? false : null);
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->expectRejected(fn () => $this->mutate($operation, $target, in_array($operation, ['archive', 'restore'], true) ? [$second->id, $target->id] : null));
        $this->assertEquals($before, $this->snapshot());
    }

    #[DataProvider('auditFaults')]
    public function test_audit_faults_rollback_lifecycle_history_authority_and_assignments(string $operation, string $fault): void
    {
        [$lab, $peer, $actor, $target] = $this->fixture($operation);
        activity('staff_account')->causedBy($actor)->performedOn($target)->event('updated')
            ->withProperties(['target_user_id' => $target->id])->log('Retained account evidence');
        $retained = ISOActivityLog::withoutGlobalScopes()->where('log_name', 'staff_account')->sole();
        $before = $this->snapshot();
        if ($fault === 'disabled') {
            activity()->disableLogging();
        }
        ISOActivityLog::creating(function (ISOActivityLog $audit) use ($fault, $lab, $peer, $actor, $target, $retained): ?bool {
            if ($audit->log_name !== 'staff_account') {
                return null;
            }
            if ($fault === 'veto') {
                return false;
            }
            match ($fault) {
                'audit' => $audit->description = 'forged lifecycle evidence',
                'properties' => $audit->properties = collect(['target_user_id' => $target->id, 'password' => 'FORGED']),
                'identity' => $audit->subject_id = $actor->id,
                'retained' => DB::table('activity_log')->where('id', $retained->id)->update(['description' => 'forged retained evidence']),
                'root' => DB::table('users')->where('id', $target->id)->update(['email' => 'forged@example.test']),
                'department_pivot' => DB::table('department_user')->where('user_id', $target->id)->update(['deleted_at' => now()]),
                'role_pivot' => DB::table('model_has_roles')->where('model_id', $target->id)->delete(),
                'permission_pivot' => DB::table('model_has_permissions')->where('model_id', $target->id)->delete(),
                'qualification' => DB::table('personnel_qualifications')->where('user_id', $target->id)->update(['training_reference' => 'FORGED']),
                'peer_membership' => DB::table('lab_user')->where('user_id', $target->id)->where('lab_id', $peer->id)->delete(),
                'target_membership' => DB::table('lab_user')->where('user_id', $target->id)->where('lab_id', $lab->id)->delete(),
                'passkey' => DB::table('passkeys')->where('authenticatable_id', $target->id)->update(['name' => 'FORGED']),
                'token' => DB::table('personal_access_tokens')->where('tokenable_id', $target->id)->delete(),
                'actor_membership' => DB::table('lab_user')->where('user_id', $actor->id)->where('lab_id', $lab->id)->delete(),
                'actor_role' => DB::table('model_has_roles')->where('model_id', $actor->id)->delete(),
                'actor_state' => DB::table('users')->where('id', $actor->id)->update(['is_active' => false]),
                'actor_verified' => DB::table('users')->where('id', $actor->id)->update(['email_verified_at' => null]),
                'laboratory' => DB::table('labs')->where('id', $lab->id)->update(['deleted_at' => now()]),
                'impersonation' => request()->session()->put('impersonate', $actor->id),
                default => null,
            };

            return null;
        });
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        try {
            $this->expectRejected(fn () => $this->mutate($operation, $target));
        } finally {
            activity()->enableLogging();
        }
        $this->assertEquals($before, $this->snapshot());
    }

    #[DataProvider('operations')]
    public function test_successful_lifecycle_hooks_cannot_change_unrelated_account_identity(string $operation): void
    {
        [$lab, , $actor, $target] = $this->fixture($operation);
        $before = $this->snapshot();
        $event = match ($operation) {
            'archive' => 'deleted', 'restore' => 'restored', default => 'saved'
        };
        User::{$event}(function (User $user) use ($target): void {
            if ($user->id === $target->id) {
                DB::table('users')->where('id', $target->id)->update(['name' => 'FORGED-LIFECYCLE']);
            }
        });
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->expectRejected(fn () => $this->mutate($operation, $target));
        $this->assertEquals($before, $this->snapshot());
    }

    public function test_second_batch_audit_cannot_corrupt_the_first_target_or_its_history(): void
    {
        [$lab, , $actor, $target] = $this->fixture('archive');
        $second = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $second->id]);
        $before = $this->snapshot();
        ISOActivityLog::creating(function (ISOActivityLog $audit) use ($target, $second): void {
            if ($audit->log_name === 'staff_account' && (int) $audit->subject_id === $second->id) {
                DB::table('users')->where('id', $target->id)->update(['email' => 'forged@example.test']);
                DB::table('activity_log')->where('log_name', 'staff_account')->where('subject_id', $target->id)->update(['description' => 'FORGED-FIRST-AUDIT']);
            }
        });
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->expectRejected(fn () => $this->mutate('archive', $target, [$second->id, $target->id]));
        $this->assertEquals($before, $this->snapshot());
    }

    public function test_administrator_can_reset_own_password_without_changing_own_authority(): void
    {
        [$lab, , $actor] = $this->fixture('password');
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->mutate('password', $actor)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewStr0ng!Value123', $actor->fresh()->password));
        $this->assertTrue($actor->fresh()->hasRole('admin', 'web'));
        $this->assertDatabaseHas('lab_user', ['lab_id' => $lab->id, 'user_id' => $actor->id]);
    }

    /** @return array<string,array{string}> */
    public static function operations(): array
    {
        $operations = ['archive', 'restore', 'activate', 'deactivate', 'password'];

        return array_combine($operations, array_map(fn (string $operation): array => [$operation], $operations));
    }

    /** @return array<string,array{string,string}> */
    public static function auditFaults(): array
    {
        $cases = [];
        $faults = ['disabled', 'veto', 'audit', 'properties', 'identity', 'retained', 'root', 'department_pivot', 'role_pivot', 'permission_pivot',
            'qualification', 'peer_membership', 'target_membership', 'passkey', 'token', 'actor_membership', 'actor_role', 'actor_state', 'actor_verified', 'laboratory', 'impersonation'];
        foreach (array_keys(self::operations()) as $operation) {
            foreach ($faults as $fault) {
                $cases[$operation.'_'.$fault] = [$operation, $fault];
            }
        }

        return $cases;
    }

    /** @return array{VAPLab,VAPLab,User,User} */
    private function fixture(string $operation): array
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $actor = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $actor->assignRole(Role::findOrCreate('admin', 'web'));
        foreach (['view_users', 'delete_users', 'restore_users', 'ban_users', 'reset-password_users'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $target = User::factory()->create(['is_active' => $operation !== 'activate', 'email_verified_at' => now(),
            'two_factor_secret' => '2FA-MARKER', 'two_factor_recovery_codes' => 'RECOVERY-MARKER', 'microsoft_data' => ['token' => 'OAUTH-MARKER']]);
        $target->departments()->attach(Department::factory()->create());
        $target->assignRole(Role::findOrCreate('lifecycle-scientist', 'web'));
        $target->givePermissionTo(Permission::findOrCreate('view_samples', 'web'));
        foreach ([[$lab, $actor], [$lab, $target], [$peer, $target]] as [$owner, $user]) {
            DB::table('lab_user')->insert(['lab_id' => $owner->id, 'user_id' => $user->id]);
        }
        foreach ([$lab, $peer] as $owner) {
            PersonnelQualification::query()->create(['lab_id' => $owner->id, 'user_id' => $target->id, 'capability' => 'verify_results', 'training_reference' => 'KEEP-'.$owner->id, 'is_active' => true]);
        }
        DB::table('passkeys')->insert(['authenticatable_id' => $target->id, 'authenticatable_type' => $target->getMorphClass(),
            'name' => 'KEEP', 'credential_id' => fake()->uuid(), 'data' => json_encode(['key' => 'PASSKEY-MARKER']), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('passkeys')->insert(['authenticatable_id' => $target->id, 'authenticatable_type' => User::class,
            'name' => 'KEEP-CLASS', 'credential_id' => fake()->uuid(), 'data' => json_encode(['key' => 'PASSKEY-CLASS-MARKER']), 'created_at' => now(), 'updated_at' => now()]);
        $target->createToken('API-MARKER');
        if ($operation === 'restore') {
            $target->delete();
        }

        return [$lab, $peer, $actor, $target];
    }

    private function mutate(string $operation, User $target, ?array $ids = null): TestResponse
    {
        return match ($operation) {
            'archive', 'restore' => $this->post(route($operation === 'archive' ? 'users.destroy' : 'users.restore'), ['recordIds' => $ids ?? [$target->id]]),
            'activate', 'deactivate' => $this->post(route('users.setActiveStatus', $target), ['is_active' => $operation === 'activate']),
            'password' => $this->put(route('users.setpass', $target), ['password' => 'NewStr0ng!Value123', 'password_confirmation' => 'NewStr0ng!Value123']),
        };
    }

    /** @param list<string>|null $tables
     * @return array<string,array<mixed>>
     */
    private function snapshot(?array $tables = null): array
    {
        $evidence = [];
        foreach ($tables ?? ['users', 'labs', 'lab_user', 'personnel_qualifications', 'department_user', 'model_has_roles', 'model_has_permissions', 'passkeys', 'personal_access_tokens', 'media', 'activity_log'] as $table) {
            $evidence[$table] = DB::table($table)->get()->toArray();
        }

        return $evidence;
    }

    private function expectRejected(\Closure $mutation): void
    {
        $this->withoutExceptionHandling();
        try {
            $mutation();
            $this->fail('Expected lifecycle mutation to fail closed.');
        } catch (\LogicException|AuthorizationException|HttpException $exception) {
            $this->addToAssertionCount(1);
        }
    }
}
