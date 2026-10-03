<?php

namespace Tests\Feature;

use App\Models\ISOActivityLog;
use App\Models\Permission;
use App\Models\PersonnelQualification;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LaboratoryMembershipTest extends TestCase
{
    use DatabaseTransactions;

    public function test_local_administrator_can_join_and_remove_a_shared_account_without_changing_peer_evidence(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $actor = $this->member($lab);
        $target = $this->member($peer);
        $qualification = PersonnelQualification::query()->create(['lab_id' => $peer->id, 'user_id' => $target->id, 'capability' => 'Peer approval', 'is_active' => true]);
        $before = $target->fresh()->getAttributes();
        $peerBefore = DB::table('lab_user')->where('user_id', $target->id)->first();
        $qualificationBefore = $qualification->fresh()->getAttributes();
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->post(route('users.membership.store'), ['email' => $target->email])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lab_user', ['lab_id' => $lab->id, 'user_id' => $target->id, 'can_view_network' => false, 'can_manage_branding' => false]);
        $this->assertSame($before, $target->fresh()->getAttributes());
        $this->assertEquals($peerBefore, DB::table('lab_user')->where('id', $peerBefore->id)->first());
        $this->assertSame($qualificationBefore, $qualification->fresh()->getAttributes());
        $this->assertFalse($target->hasRole('admin'));
        $this->post(route('users.membership.store'), ['email' => $target->email])->assertSessionHasNoErrors();
        $this->assertSame(1, $this->audits()->where('event', 'membership_added')->count());
        PersonnelQualification::query()->create(['lab_id' => $lab->id, 'user_id' => $target->id, 'capability' => 'Local approval', 'is_active' => true]);
        $this->delete(route('users.membership.destroy', $target))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('lab_user', ['lab_id' => $lab->id, 'user_id' => $target->id]);
        $this->assertSame(2, $target->personnelQualifications()->count());
        $this->assertSame($before, $target->fresh()->getAttributes());
        $this->assertEquals($peerBefore, DB::table('lab_user')->where('id', $peerBefore->id)->first());
        $this->assertSame($qualificationBefore, $qualification->fresh()->getAttributes());
        $this->assertSame(1, $this->audits()->where('event', 'membership_removed')->count());
        $this->delete(route('users.membership.destroy', $target))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1, $this->audits()->where('event', 'membership_removed')->count());
        $this->get(route('users.edit', $target))->assertNotFound();
    }

    public function test_membership_history_is_private_to_eligible_local_staff_and_does_not_serialize_shared_accounts(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $actor = $this->member($lab);
        $peerActor = $this->member($peer);
        $target = $this->member($peer);
        $actor->givePermissionTo(Permission::findOrCreate('view_activity_log', 'web'));
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->post(route('users.membership.store'), ['email' => $target->email])->assertSessionHasNoErrors();
        $local = $this->audits()->sole();
        $this->actingAs($peerActor)->withSession(['active_lab_id' => $peer->id]);
        $this->delete(route('users.membership.destroy', $target))->assertSessionHasNoErrors();
        $foreign = $this->audits()->where('event', 'membership_removed')->sole();
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $response = $this->get(route('systemactivity.index'))->assertOk();
        $ids = collect(data_get($response->viewData('page'), 'props.record.data'))->pluck('id');
        $this->assertContains($local->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
        $this->getJson(route('systemactivity.show', $foreign->id))->assertNotFound();
        $this->getJson(route('systemactivity.show', $local->id))->assertOk()->assertJsonPath('activity.subject.id', $lab->id)->assertDontSee($target->email);
        $this->assertSame(0, ISOActivityLog::query()->where('log_name', 'laboratory_membership')->count());
        request()->attributes->set('proposal_laboratory_id', $lab->id);
        request()->setUserResolver(fn () => $actor);
        $this->assertSame(1, ISOActivityLog::query()->where('log_name', 'laboratory_membership')->count());
        $actor->revokePermissionTo('view_users');
        $this->getJson(route('systemactivity.show', $local->id))->assertNotFound();
    }

    public function test_membership_validation_and_permissions_do_not_allow_global_or_foreign_mutations(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $actor = $this->member($lab);
        $target = $this->member($peer);
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->get(route('users.membership.store'))->assertStatus(405);
        $this->get(route('users.membership.destroy', $target))->assertStatus(405);
        $this->delete(route('users.membership.destroy', $target))->assertNotFound();
        $this->delete(route('users.membership.destroy', $actor))->assertStatus(422);
        foreach (['roles' => [1], 'lab_id' => $peer->id, 'is_active' => false, 'can_view_network' => true, 'password' => 'forged'] as $field => $value) {
            $this->post(route('users.membership.store'), ['email' => $target->email, $field => $value])->assertSessionHasErrors($field);
        }
        foreach ([null, 'invalid', 'absent@example.test'] as $email) {
            $this->post(route('users.membership.store'), ['email' => $email])->assertSessionHasErrors('email');
        }
        $target->forceFill(['is_active' => false])->save();
        $this->post(route('users.membership.store'), ['email' => $target->email])->assertSessionHasErrors('email');
        $target->forceFill(['is_active' => true, 'email_verified_at' => null])->save();
        $this->post(route('users.membership.store'), ['email' => $target->email])->assertSessionHasErrors('email');
        $actor->syncPermissions([]);
        $this->post(route('users.membership.store'), ['email' => $target->email])->assertForbidden();
        $this->delete(route('users.membership.destroy', $target))->assertForbidden();
        $this->assertDatabaseMissing('lab_user', ['lab_id' => $lab->id, 'user_id' => $target->id]);
    }

    #[DataProvider('membershipFaults')]
    public function test_membership_and_audit_fail_atomically_when_lifecycle_work_changes_intent_or_authority(string $fault, bool $joining): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $actor = $this->member($lab);
        $target = $this->member($peer);
        if (! $joining) {
            DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $target->id]);
        }
        $before = DB::table('lab_user')->orderBy('id')->get()->toArray();
        $targetBefore = $target->fresh()->getAttributes();
        $labBefore = $lab->fresh()->getAttributes();
        ISOActivityLog::creating(function (ISOActivityLog $audit) use ($fault, $actor, $target, $lab, $peer): ?bool {
            if ($audit->log_name !== 'laboratory_membership') {
                return null;
            }
            if ($fault === 'veto') {
                return false;
            }
            if ($fault === 'audit') {
                $audit->description = 'Forged membership evidence';
            } elseif ($fault === 'target') {
                DB::table('users')->where('id', $target->id)->update(['email' => 'forged@example.test']);
            } elseif ($fault === 'peer') {
                DB::table('lab_user')->where('lab_id', $peer->id)->where('user_id', $target->id)->delete();
            } elseif ($fault === 'actor') {
                DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $actor->id)->delete();
            } elseif ($fault === 'lab') {
                VAPLab::query()->whereKey($lab->id)->delete();
            } elseif ($fault === 'lab_name') {
                DB::table($lab->getTable())->where('id', $lab->id)->update(['name' => 'Forged laboratory name']);
            } elseif ($fault === 'lab_branding') {
                DB::table($lab->getTable())->where('id', $lab->id)->update(['primary_color' => '#123456']);
            } elseif ($fault === 'lab_timestamp') {
                DB::table($lab->getTable())->where('id', $lab->id)->update(['updated_at' => now()->subDay()]);
            }

            return null;
        });
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $response = $joining ? $this->post(route('users.membership.store'), ['email' => $target->email]) : $this->delete(route('users.membership.destroy', $target));
        $response->assertStatus(in_array($fault, ['actor', 'lab'], true) ? 403 : 409);
        $this->assertEquals($before, DB::table('lab_user')->orderBy('id')->get()->toArray());
        $this->assertSame($targetBefore, $target->fresh()->getAttributes());
        $this->assertSame(0, $this->audits()->count());
        $this->assertNull($lab->fresh()->deleted_at);
        $this->assertSame($labBefore, $lab->fresh()->getAttributes());
    }

    /** @return array<string,array{string,bool}> */
    public static function membershipFaults(): array
    {
        $cases = [];
        foreach (['veto', 'audit', 'target', 'peer', 'actor', 'lab', 'lab_name', 'lab_branding', 'lab_timestamp'] as $fault) {
            foreach ([true, false] as $joining) {
                $cases[$fault.($joining ? '_join' : '_remove')] = [$fault, $joining];
            }
        }

        return $cases;
    }

    #[DataProvider('operatorEvidenceFaults')]
    public function test_membership_preserves_operator_identity_and_exact_local_membership(string $fault, bool $joining, string $stage): void
    {
        $this->freezeTime();
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $actor = $this->member($lab);
        $actor->forceFill(['last_activity_at' => now()])->saveQuietly();
        $target = $this->member($peer);
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        if ($stage === 'replay') {
            $this->post(route('users.membership.store'), ['email' => $target->email])->assertSessionHasNoErrors();
            if (! $joining) {
                $this->delete(route('users.membership.destroy', $target))->assertSessionHasNoErrors();
            }
        } elseif (! $joining) {
            DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $target->id]);
        }
        $tables = ['users', 'labs', 'lab_user', 'activity_log'];
        $before = $this->snapshotTables($tables);
        $fired = false;
        $mutate = function () use ($actor, $lab, $fault, &$fired): void {
            if ($fired) {
                return;
            }
            $fired = true;
            $pivot = DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $actor->id)->first();
            if ($fault === 'account') {
                DB::table($actor->getTable())->where('id', $actor->id)->update(['name' => 'Forged operator identity']);
            } elseif ($fault === 'pivot_replaced') {
                $attributes = (array) $pivot;
                unset($attributes['id']);
                DB::table('lab_user')->where('id', $pivot->id)->delete();
                DB::table('lab_user')->insert($attributes);
            } else {
                $changes = match ($fault) {
                    'network' => ['can_view_network' => ! $pivot->can_view_network],
                    'branding' => ['can_manage_branding' => ! $pivot->can_manage_branding],
                    'timestamp' => ['updated_at' => now()->subDay()],
                };
                DB::table('lab_user')->where('id', $pivot->id)->update($changes);
            }
        };
        if ($stage === 'audit') {
            ISOActivityLog::creating(function (ISOActivityLog $audit) use ($mutate): void {
                if ($audit->log_name === 'laboratory_membership') {
                    $mutate();
                }
            });
        } else {
            $armed = $stage === 'entry';
            DB::listen(function (QueryExecuted $query) use (&$armed): void {
                if (str_contains($query->sql, 'from "activity_log"') && str_contains($query->sql, 'for update')) {
                    $armed = true;
                }
            });
            User::retrieved(function (User $user) use ($actor, $mutate, &$armed): void {
                if ($armed && $user->id === $actor->id && DB::transactionLevel() >= 2) {
                    $mutate();
                }
            });
        }
        $response = $joining ? $this->post(route('users.membership.store'), ['email' => $target->email]) : $this->delete(route('users.membership.destroy', $target));
        $response->assertStatus(409);
        $this->assertTrue($fired);
        $this->assertSame($before, $this->snapshotTables($tables));
    }

    /** @return array<string,array{string,bool,string}> */
    public static function operatorEvidenceFaults(): array
    {
        $cases = [];
        foreach (['account', 'network', 'branding', 'timestamp', 'pivot_replaced'] as $fault) {
            foreach ([true, false] as $joining) {
                foreach (['entry', 'audit', 'final', 'replay'] as $stage) {
                    $cases[$fault.($joining ? '_join_' : '_remove_').$stage] = [$fault, $joining, $stage];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('retainedEvidenceFaults')]
    public function test_membership_preserves_retained_history_and_shared_credentials(string $fault, bool $joining): void
    {
        $this->freezeTime();
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $actor = $this->member($lab);
        $actor->forceFill(['last_activity_at' => now()])->saveQuietly();
        $target = $this->member($peer);
        if (! $joining) {
            DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $target->id]);
        }
        $retained = activity('staff_account')->performedOn($target)->causedBy($actor)
            ->withProperties(['target_user_id' => $target->id, 'origin_lab_id' => $peer->id])->log('Retained account evidence');
        $target->createToken('Retained test credential');
        DB::table('passkeys')->insert(['authenticatable_id' => $target->id, 'authenticatable_type' => $target->getMorphClass(),
            'name' => 'Retained test key', 'credential_id' => fake()->uuid(), 'data' => json_encode(['test' => true]),
            'created_at' => now(), 'updated_at' => now()]);
        DB::table('media')->insert(['model_id' => $target->id, 'model_type' => $target->getMorphClass(),
            'collection_name' => 'staff_evidence', 'name' => 'Retained evidence', 'file_name' => 'retained.pdf',
            'disk' => 'public', 'size' => 1, 'manipulations' => '{}', 'custom_properties' => '{}',
            'generated_conversions' => '{}', 'responsive_images' => '{}']);
        $tables = ['users', 'labs', 'lab_user', 'activity_log', 'personal_access_tokens', 'passkeys', 'media'];
        $before = $this->snapshotTables($tables);
        ISOActivityLog::creating(function (ISOActivityLog $audit) use ($fault, $retained, $target): void {
            if ($audit->log_name !== 'laboratory_membership') {
                return;
            }
            match ($fault) {
                'history_changed' => DB::table('activity_log')->where('id', $retained->id)->update(['description' => 'Forged history']),
                'history_deleted' => DB::table('activity_log')->where('id', $retained->id)->delete(),
                'history_moved' => DB::table('activity_log')->where('id', $retained->id)->update(['properties' => '{}']),
                'history_extra' => DB::table('activity_log')->insert(['log_name' => 'staff_account', 'description' => 'Unexpected history',
                    'properties' => json_encode(['target_user_id' => $target->id])]),
                'created_at' => $audit->setCreatedAt(now()->subDay()),
                'updated_at' => $audit->setUpdatedAt(now()->subDay()),
                'batch_uuid' => $audit->batch_uuid = fake()->uuid(),
                'token' => DB::table('personal_access_tokens')->where('tokenable_id', $target->id)->delete(),
                'passkey' => DB::table('passkeys')->where('authenticatable_id', $target->id)->update(['name' => 'Forged key']),
                'media' => DB::table('media')->where('model_id', $target->id)->update(['file_name' => 'forged.pdf']),
            };
        });
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $response = $joining ? $this->post(route('users.membership.store'), ['email' => $target->email]) : $this->delete(route('users.membership.destroy', $target));
        $response->assertStatus(409);
        $this->assertSame($before, $this->snapshotTables($tables));
    }

    /** @return array<string,array{string,bool}> */
    public static function retainedEvidenceFaults(): array
    {
        $cases = [];
        foreach (['history_changed', 'history_deleted', 'history_moved', 'history_extra', 'created_at', 'updated_at', 'batch_uuid', 'token', 'passkey', 'media'] as $fault) {
            foreach ([true, false] as $joining) {
                $cases[$fault.($joining ? '_join' : '_remove')] = [$fault, $joining];
            }
        }

        return $cases;
    }

    public function test_final_membership_history_reads_do_not_run_model_retrieval_hooks(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $actor = $this->member($lab);
        $target = $this->member($peer);
        $retrievals = 0;
        ISOActivityLog::retrieved(function (ISOActivityLog $audit) use (&$retrievals): void {
            if ($audit->log_name === 'laboratory_membership') {
                $retrievals++;
            }
        });
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->post(route('users.membership.store'), ['email' => $target->email])->assertRedirect()->assertSessionHasNoErrors();
        $this->delete(route('users.membership.destroy', $target))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(0, $retrievals);
    }

    #[DataProvider('replayFaults')]
    public function test_membership_replay_rechecks_authority_and_retained_evidence(string $fault, bool $joining): void
    {
        $this->freezeTime();
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $actor = $this->member($lab);
        $target = $this->member($peer);
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->post(route('users.membership.store'), ['email' => $target->email])->assertSessionHasNoErrors();
        if (! $joining) {
            $this->delete(route('users.membership.destroy', $target))->assertSessionHasNoErrors();
        }
        $before = $this->snapshotTables(['users', 'labs', 'lab_user', 'activity_log']);
        $armed = false;
        $fired = false;
        DB::listen(function (QueryExecuted $query) use (&$armed): void {
            if (str_contains($query->sql, 'from "activity_log"') && str_contains($query->sql, 'for update')) {
                $armed = true;
            }
        });
        User::retrieved(function (User $user) use ($actor, $target, $lab, $fault, &$armed, &$fired): void {
            if (! $armed || $fired || $user->id !== $actor->id) {
                return;
            }
            $fired = true;
            if ($fault === 'authority') {
                DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $actor->id)->delete();
            } else {
                DB::table('users')->where('id', $target->id)->update(['name' => 'Forged replay account']);
            }
        });
        $response = $joining ? $this->post(route('users.membership.store'), ['email' => $target->email]) : $this->delete(route('users.membership.destroy', $target));
        $response->assertStatus($fault === 'authority' ? 403 : 409);
        $this->assertTrue($fired);
        $this->assertSame($before, $this->snapshotTables(['users', 'labs', 'lab_user', 'activity_log']));
    }

    /** @return array<string,array{string,bool}> */
    public static function replayFaults(): array
    {
        return ['join_authority' => ['authority', true], 'remove_authority' => ['authority', false],
            'join_evidence' => ['evidence', true], 'remove_evidence' => ['evidence', false]];
    }

    #[DataProvider('joinEligibilityChanges')]
    public function test_join_reloads_target_eligibility_after_actor_model_hooks(string $field, mixed $value): void
    {
        $this->freezeTime();
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $actor = $this->member($lab);
        $actor->forceFill(['last_activity_at' => now()])->saveQuietly();
        $target = $this->member($peer);
        $before = $this->snapshotTables(['users', 'labs', 'lab_user', 'activity_log']);
        $fired = false;
        User::retrieved(function (User $user) use ($actor, $target, $field, $value, &$fired): void {
            if ($fired || $user->id !== $actor->id || DB::transactionLevel() < 2) {
                return;
            }
            $fired = true;
            DB::table('users')->where('id', $target->id)->update([$field => $value]);
        });
        $this->actingAs($actor)->withSession(['active_lab_id' => $lab->id]);
        $this->post(route('users.membership.store'), ['email' => $target->email])->assertSessionHasErrors('email');
        $this->assertTrue($fired);
        $this->assertSame($before, $this->snapshotTables(['users', 'labs', 'lab_user', 'activity_log']));
    }

    /** @return array<string,array{string,mixed}> */
    public static function joinEligibilityChanges(): array
    {
        return ['inactive' => ['is_active', false], 'unverified' => ['email_verified_at', null],
            'changed_email' => ['email', 'changed-target@example.test'], 'archived' => ['deleted_at', '2026-01-01 00:00:00']];
    }

    /** @param list<string> $tables
     * @return array<string,list<array<string,mixed>>>
     */
    private function snapshotTables(array $tables): array
    {
        $snapshot = [];
        foreach ($tables as $table) {
            $snapshot[$table] = DB::table($table)->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
        }

        return $snapshot;
    }

    private function member(VAPLab $lab): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        foreach (['add_users', 'delete_users', 'edit_users', 'view_users'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        return $user;
    }

    private function audits(): Builder
    {
        return ISOActivityLog::withoutGlobalScopes()->where('log_name', 'laboratory_membership');
    }
}
