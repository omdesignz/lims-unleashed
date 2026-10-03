<?php

namespace Tests\Feature;

use App\Models\ISOActivityLog;
use App\Models\Permission;
use App\Models\PersonnelQualification;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Support\ExportHubQuery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class StaffAccountHistoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_mixed_changes_retain_separate_allowlisted_global_and_local_history(): void
    {
        [$lab, $peer, $actor, $target, $local, $foreign] = $this->fixture();
        $target->forceFill(['two_factor_secret' => 'TARGET-2FA-SECRET', 'two_factor_recovery_codes' => 'TARGET-RECOVERY',
            'microsoft_data' => ['token' => 'TARGET-OAUTH-SECRET'], 'primary_phone' => 'PRIVATE-PHONE'])->save();
        $oldName = $target->name;
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->put(route('users.update', $target), $this->payload())->assertRedirect()->assertSessionHasNoErrors();
        $global = $this->audits()->where('log_name', 'staff_account')->sole();
        $qualification = $this->audits()->where('log_name', 'personnel_qualifications')->sole();
        $this->assertSame($target->id, (int) $global->subject_id);
        $this->assertSame($target->getMorphClass(), $global->subject_type);
        $this->assertSame(['name' => ['old' => $oldName, 'new' => 'Revised :subject.email']], $global->properties['changes']);
        $this->assertSame('actualizou os dados ou acessos de uma conta partilhada', $global->description);
        $this->assertSame($lab->getMorphClass(), $qualification->subject_type);
        $this->assertSame($lab->id, (int) $qualification->subject_id);
        $this->assertSame($local->id, $qualification->properties['old'][0]['id']);
        $this->assertSame('LOCAL-OLD-EVIDENCE', $qualification->properties['old'][0]['training_reference']);
        $this->assertSame('LOCAL-NEW-EVIDENCE', $qualification->properties['new'][0]['training_reference']);
        $this->assertSame($actor->id, $qualification->properties['new'][0]['qualified_by_id']);
        $this->assertDatabaseMissing('personnel_qualifications', ['id' => $local->id]);
        $this->assertModelExists($foreign);
        $history = $this->audits()->get()->toJson();
        foreach (['TARGET-2FA-SECRET', 'TARGET-RECOVERY', 'TARGET-OAUTH-SECRET', 'PRIVATE-PHONE', 'PEER-EVIDENCE'] as $marker) {
            $this->assertStringNotContainsString($marker, $history);
        }
        $this->put(route('users.update', $target), ['personnel_qualifications' => []])->assertSessionHasNoErrors();
        $latest = $this->audits()->where('log_name', 'personnel_qualifications')->latest('id')->firstOrFail();
        $this->assertCount(1, $latest->properties['old']);
        $this->assertSame([], $latest->properties['new']);
        $this->assertSame('LOCAL-OLD-EVIDENCE', $qualification->fresh()->properties['old'][0]['training_reference']);
        $this->assertSame($peer->id, $foreign->fresh()->lab_id);
    }

    public function test_global_access_changes_are_recorded_without_noop_profile_audits(): void
    {
        [$lab, , $actor, $target] = $this->fixture();
        $role = Role::findOrCreate('audited-scientist', 'web');
        $permission = Permission::findOrCreate('view_samples', 'web');
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->put(route('users.update', $target), ['roles' => [$role->id], 'permissions' => [$permission->id]])->assertSessionHasNoErrors();
        $audit = $this->audits()->sole();
        $this->assertSame(['old' => [], 'new' => [$role->id]], $audit->properties['changes']['roles']);
        $this->assertSame(['old' => [], 'new' => [$permission->id]], $audit->properties['changes']['permissions']);
        $this->put(route('users.update', $target), ['name' => $target->name, 'roles' => [$role->id], 'permissions' => [$permission->id]])->assertSessionHasNoErrors();
        $this->assertSame(1, $this->audits()->count());
        $this->put(route('users.update', $target), ['roles' => [], 'permissions' => []])->assertSessionHasNoErrors();
        $latest = $this->audits()->latest('id')->firstOrFail();
        $this->assertSame(['old' => [$role->id], 'new' => []], $latest->properties['changes']['roles']);
    }

    public function test_history_visibility_distinguishes_account_owner_system_admin_and_local_staff(): void
    {
        [$lab, $peer, $actor, $target] = $this->fixture();
        $editor = $this->member($lab);
        $peerEditor = $this->member($peer);
        foreach ([$target, $editor, $peerEditor] as $viewer) {
            $viewer->givePermissionTo(['view_users', 'view_activity_log']);
        }
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->put(route('users.update', $target), $this->payload())->assertSessionHasNoErrors();
        $global = $this->audits()->where('log_name', 'staff_account')->sole();
        $local = $this->audits()->where('log_name', 'personnel_qualifications')->sole();
        foreach ([[$actor, $lab, true, true], [$target, $lab, true, true], [$target, $peer, true, false],
            [$editor, $lab, false, true], [$peerEditor, $peer, false, false]] as [$viewer, $activeLab, $seeAccount, $seeLocal]) {
            $this->actingAs($viewer)->withSession(['active_lab_id' => $activeLab->id]);
            $response = $this->get(route('systemactivity.index'))->assertOk();
            $ids = collect(data_get($response->viewData('page'), 'props.record.data'))->pluck('id')->all();
            $this->assertSame($seeAccount, in_array($global->id, $ids, true));
            $this->assertSame($seeLocal, in_array($local->id, $ids, true));
            $this->getJson(route('systemactivity.show', $global->id))->assertStatus($seeAccount ? 200 : 404);
            $this->getJson(route('systemactivity.show', $local->id))->assertStatus($seeLocal ? 200 : 404);
            request()->attributes->set('proposal_laboratory_id', $activeLab->id);
            request()->setUserResolver(fn () => $viewer);
            $visibleCustom = ISOActivityLog::query()->whereIn('id', [$global->id, $local->id])->pluck('id')->all();
            $visibleExport = app(ExportHubQuery::class)->forDataset('activity_log', [])->whereIn('activity_log.id', [$global->id, $local->id])->pluck('activity_log.id')->all();
            $this->assertSame($seeAccount, in_array($global->id, $visibleCustom, true));
            $this->assertSame($seeLocal, in_array($local->id, $visibleCustom, true));
            $this->assertEqualsCanonicalizing($visibleCustom, $visibleExport);
        }
        request()->attributes->remove('proposal_laboratory_id');
        $this->assertSame(0, ISOActivityLog::query()->whereIn('id', [$global->id, $local->id])->count());
    }

    public function test_activity_detail_never_serializes_user_security_or_unrelated_profile_fields(): void
    {
        [$lab, , $actor, $target] = $this->fixture();
        foreach ([$actor, $target] as $user) {
            $user->forceFill(['two_factor_secret' => 'SECRET-'.$user->id, 'two_factor_recovery_codes' => 'RECOVERY-'.$user->id,
                'microsoft_data' => ['token' => 'OAUTH-'.$user->id], 'id_number' => 'IDENTITY-'.$user->id])->save();
        }
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->put(route('users.update', $target), $this->payload())->assertSessionHasNoErrors();
        $generic = activity('ordinary')->causedBy($actor)->performedOn($target)->log('ordinary user audit');
        foreach ([...$this->audits()->get()->all(), $generic] as $audit) {
            $response = $this->getJson(route('systemactivity.show', $audit->id))->assertOk();
            $this->assertSame(['id' => $actor->id, 'name' => $actor->name], $response->json('activity.causer'));
            if ($audit->subject_type === $target->getMorphClass()) {
                $this->assertSame(['id' => $target->id, 'name' => 'Revised :subject.email'], $response->json('activity.subject'));
            }
            foreach (['SECRET-', 'RECOVERY-', 'OAUTH-', 'IDENTITY-', 'two_factor_secret', 'microsoft_data', 'primary_phone'] as $marker) {
                $response->assertDontSee($marker);
            }
        }
        $index = $this->get(route('systemactivity.index'))->assertOk();
        $rows = collect(data_get($index->viewData('page'), 'props.record.data'))->keyBy('id');
        foreach ($this->audits()->get() as $audit) {
            $this->assertTrue($rows[$audit->id]['is_retained']);
            $this->assertSame(['id' => $actor->id, 'name' => $actor->name, 'email' => $actor->email], $rows[$audit->id]['causer']);
        }
        $this->assertFalse($rows[$generic->id]['is_retained']);
    }

    #[DataProvider('visibilityRevocations')]
    public function test_history_reads_recheck_fresh_authority_and_fail_closed_without_context(string $fault): void
    {
        [$lab, , $actor, $target] = $this->fixture();
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->put(route('users.update', $target), $this->payload())->assertSessionHasNoErrors();
        request()->attributes->set('proposal_laboratory_id', $lab->id);
        request()->setUserResolver(fn () => $actor);
        $ids = $this->audits()->pluck('id')->all();
        $this->assertSame(2, ISOActivityLog::query()->whereIn('id', $ids)->count());
        match ($fault) {
            'unverified' => DB::table('users')->where('id', $actor->id)->update(['email_verified_at' => null]),
            'inactive' => DB::table('users')->where('id', $actor->id)->update(['is_active' => false]),
            'membership' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $actor->id)->delete(),
            'laboratory' => DB::table('labs')->where('id', $lab->id)->update(['deleted_at' => now()]),
            'impersonation' => request()->session()->put('impersonate', $actor->id),
            'missing_context' => request()->attributes->remove('proposal_laboratory_id'),
            'missing_user' => request()->setUserResolver(fn () => null),
        };
        $this->assertSame(0, ISOActivityLog::query()->whereIn('id', $ids)->count());
        $this->assertSame(0, Activity::query()->whereIn('id', $ids)->count());
        $this->assertSame(0, app(ExportHubQuery::class)->forDataset('activity_log', [])->whereIn('activity_log.id', $ids)->count());
        $this->assertSame(2, $this->audits()->count());
    }

    /** @return array<string,array{string}> */
    public static function visibilityRevocations(): array
    {
        $faults = ['unverified', 'inactive', 'membership', 'laboratory', 'impersonation', 'missing_context', 'missing_user'];

        return array_combine($faults, array_map(fn (string $fault): array => [$fault], $faults));
    }

    public function test_local_history_requires_view_users_even_when_activity_access_remains(): void
    {
        [$lab, , $actor, $target] = $this->fixture();
        $viewer = $this->member($lab);
        $viewer->givePermissionTo(['view_users', 'view_activity_log']);
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->put(route('users.update', $target), $this->payload())->assertSessionHasNoErrors();
        $audit = $this->audits()->where('log_name', 'personnel_qualifications')->sole();
        $this->actingAs($viewer)->withSession(['active_lab_id' => $lab->id]);
        $this->getJson(route('systemactivity.show', $audit->id))->assertOk();
        $viewer->revokePermissionTo('view_users');
        $this->getJson(route('systemactivity.show', $audit->id))->assertNotFound();
    }

    public function test_intentional_self_email_verification_reset_still_records_only_approved_changes(): void
    {
        [$lab, , $actor] = $this->fixture();
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $email = fake()->unique()->safeEmail();
        $this->put(route('users.update', $actor), ['email' => $email, 'personnel_qualifications' => [['capability' => 'verify_results', 'is_active' => true]]])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($actor->fresh()->email_verified_at);
        $this->assertSame($email, $actor->fresh()->email);
        $audit = $this->audits()->where('log_name', 'staff_account')->sole();
        $this->assertSame($email, $audit->properties['changes']['email']['new']);
        $this->assertNull($audit->properties['changes']['email_verified_at']['new']);
        $this->assertSame(1, $this->audits()->where('log_name', 'personnel_qualifications')->count());
    }

    public function test_cleanup_and_archive_keep_canonical_staff_history_in_place(): void
    {
        [$lab, , $actor, $target] = $this->fixture();
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->put(route('users.update', $target), $this->payload())->assertSessionHasNoErrors();
        $ids = $this->audits()->pluck('id')->all();
        DB::table('activity_log')->whereIn('id', $ids)->update(['created_at' => now()->subYears(2)]);
        $generic = activity('ordinary')->causedBy($actor)->log('removable operational audit');
        foreach ($ids as $id) {
            $this->delete(route('systemactivity.destroy', $id))->assertStatus(409);
        }
        $this->delete(route('systemactivity.destroyAll'))->assertRedirect();
        $this->assertDatabaseMissing('activity_log', ['id' => $generic->id]);
        $this->assertSame(2, $this->audits()->count());
        $this->postJson(route('systemactivity.archive'), ['archive_older_than' => 6])->assertOk()->assertJsonPath('count', 0);
        $this->assertEqualsCanonicalizing($ids, $this->audits()->pluck('id')->all());
    }

    #[DataProvider('auditFaults')]
    public function test_audit_faults_roll_back_both_histories_and_all_dossier_evidence(string $fault): void
    {
        [$lab, , $actor, $target, , $peerQualification] = $this->fixture();
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->put(route('users.update', $target), ['name' => 'Earlier audited name'])->assertSessionHasNoErrors();
        if (in_array($fault, ['membership_history', 'membership_history_deleted'], true)) {
            $this->delete(route('users.membership.destroy', $target))->assertSessionHasNoErrors();
            $this->post(route('users.membership.store'), ['email' => $target->email])->assertSessionHasNoErrors();
        }
        $before = [];
        foreach (['users', 'lab_user', 'personnel_qualifications', 'model_has_roles', 'model_has_permissions', 'department_user', 'activity_log'] as $table) {
            $before[$table] = DB::table($table)->get()->toArray();
        }
        if ($fault === 'disabled') {
            activity()->disableLogging();
        }
        ISOActivityLog::creating(function (ISOActivityLog $audit) use ($fault, $lab, $actor, $target, $peerQualification): ?bool {
            if (! in_array($audit->log_name, ['staff_account', 'personnel_qualifications'], true)) {
                return null;
            }
            if ($fault === 'veto') {
                return false;
            }
            if ($audit->log_name !== 'personnel_qualifications') {
                return null;
            }
            match ($fault) {
                'description' => $audit->description = 'forged audit',
                'properties' => $audit->properties = collect(['lab_id' => $lab->id, 'target_user_id' => $target->id, 'new' => []]),
                'identity' => $audit->subject_id = $target->id + $lab->id + 1000,
                'first_audit' => DB::table('activity_log')->where('log_name', 'staff_account')->update(['description' => 'forged first audit']),
                'retained' => DB::table('activity_log')->where('id', DB::table('activity_log')->where('log_name', 'staff_account')->min('id'))->update(['description' => 'forged retained history']),
                'membership_history' => DB::table('activity_log')->where('log_name', 'laboratory_membership')->update(['description' => 'forged retained membership']),
                'membership_history_deleted' => DB::table('activity_log')->where('log_name', 'laboratory_membership')->delete(),
                'extra_audit' => DB::table('activity_log')->insert(['log_name' => 'staff_account', 'description' => 'injected history', 'event' => 'updated',
                    'properties' => json_encode(['target_user_id' => $target->id]), 'created_at' => now(), 'updated_at' => now()]),
                'root' => DB::table('users')->where('id', $target->id)->update(['email' => 'forged@example.test']),
                'peer' => DB::table('personnel_qualifications')->where('id', $peerQualification->id)->update(['training_reference' => 'FORGED-PEER']),
                'qualification' => DB::table('personnel_qualifications')->where('lab_id', $lab->id)->where('user_id', $target->id)->update(['capability' => 'FORGED']),
                'actor_member' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $actor->id)->delete(),
                'target_member' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $target->id)->delete(),
                'peer_member' => DB::table('lab_user')->where('lab_id', $peerQualification->lab_id)->where('user_id', $target->id)->delete(),
                'actor_role' => DB::table('model_has_roles')->where('model_id', $actor->id)->delete(),
                'lab_archived' => DB::table('labs')->where('id', $lab->id)->update(['deleted_at' => now()]),
                'impersonation' => request()->session()->put('impersonate', $actor->id),
                default => null,
            };

            return null;
        });
        $this->withoutExceptionHandling();
        try {
            $this->put(route('users.update', $target), $this->payload());
            $this->fail('Expected audit mutation to fail closed.');
        } catch (\LogicException|AuthorizationException|HttpException $exception) {
            $this->addToAssertionCount(1);
        } finally {
            activity()->enableLogging();
        }
        foreach ($before as $table => $rows) {
            $this->assertEqualsCanonicalizing($rows, DB::table($table)->get()->toArray(), $table.' changed on audit failure.');
        }
    }

    /** @return array<string,array{string}> */
    public static function auditFaults(): array
    {
        $faults = ['disabled', 'veto', 'description', 'properties', 'identity', 'first_audit', 'retained', 'membership_history', 'membership_history_deleted', 'extra_audit', 'root', 'peer', 'qualification',
            'actor_member', 'target_member', 'peer_member', 'actor_role', 'lab_archived', 'impersonation'];

        return array_combine($faults, array_map(fn (string $fault): array => [$fault], $faults));
    }

    /** @return array{VAPLab,VAPLab,User,User,PersonnelQualification,PersonnelQualification} */
    private function fixture(): array
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $actor = $this->member($lab, true);
        $target = $this->member($lab);
        DB::table('lab_user')->insert(['lab_id' => $peer->id, 'user_id' => $target->id]);
        $local = PersonnelQualification::query()->create(['lab_id' => $lab->id, 'user_id' => $target->id, 'capability' => 'verify_results',
            'is_active' => true, 'training_reference' => 'LOCAL-OLD-EVIDENCE']);
        $foreign = PersonnelQualification::query()->create(['lab_id' => $peer->id, 'user_id' => $target->id, 'capability' => 'approve_results',
            'is_active' => true, 'training_reference' => 'PEER-EVIDENCE']);

        return [$lab, $peer, $actor, $target, $local, $foreign];
    }

    /** @return array<string,mixed> */
    private function payload(): array
    {
        return ['name' => 'Revised :subject.email', 'personnel_qualifications' => [['capability' => 'verify_results',
            'training_reference' => 'LOCAL-NEW-EVIDENCE', 'is_active' => true]]];
    }

    private function audits(): Builder
    {
        return ISOActivityLog::withoutGlobalScopes()->whereIn('log_name', ['staff_account', 'personnel_qualifications']);
    }

    private function member(VAPLab $lab, bool $admin = false): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        if ($admin) {
            $user->assignRole(Role::findOrCreate('admin', 'web'));
        }
        foreach (['view_users', 'view_activity_log', 'delete_activity_log', 'manage_activity_log', 'view_samples'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        return $user;
    }
}
