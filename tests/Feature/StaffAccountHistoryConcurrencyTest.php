<?php

namespace Tests\Feature;

use App\Actions\CreateStaffAccount;
use App\Actions\ManageStaffAccountLifecycle;
use App\Actions\UpdateStaffAccount;
use App\Models\Department;
use App\Models\Permission;
use App\Models\PersonnelQualification;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\IsolatedPostgresTestCase;

class StaffAccountHistoryConcurrencyTest extends IsolatedPostgresTestCase
{
    #[DataProvider('uniqueAccountIdentifiers')]
    public function test_cross_lab_creation_publishes_only_one_account_for_a_unique_identifier(string $field): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $actor = $this->administrator($lab);
        $peerActor = $this->administrator($peer);
        Permission::findOrCreate('add_users', 'web');
        $department = Department::factory()->create();
        $other = Department::factory()->create();
        $first = $this->creationPayload('first-account@example.test', $department->id);
        $second = $this->creationPayload($field === 'email' ? $first['email'] : 'second-account@example.test', $other->id);
        if ($field === 'email') {
            $second['username'] = 'second-unique-account';
        }
        $results = $this->compete([$lab->id, $peer->id], [
            ['actor' => $actor->id, 'lab' => $lab->id, 'create' => true, 'data' => $first],
            ['actor' => $peerActor->id, 'lab' => $peer->id, 'create' => true, 'data' => $second],
        ]);
        $winner = array_search(200, $results, true);
        $sorted = $results;
        sort($sorted);
        $this->assertSame([200, 422], $sorted);
        $target = User::query()->where($field, $first[$field])->sole();
        $this->assertFalse($target->hasVerifiedEmail());
        $this->assertTrue($target->is_active);
        $winningLab = $winner === 0 ? $lab : $peer;
        $winningActor = $winner === 0 ? $actor : $peerActor;
        $winningDepartment = $winner === 0 ? $department : $other;
        $membership = DB::table('lab_user')->where('user_id', $target->id)->sole();
        $this->assertSame($winningLab->id, $membership->lab_id);
        $this->assertFalse($membership->can_view_network);
        $this->assertFalse($membership->can_manage_branding);
        $this->assertSame([$winningDepartment->id], DB::table('department_user')->where('user_id', $target->id)->pluck('department_id')->all());
        $this->assertSame(0, $target->roles()->count());
        $this->assertSame(0, $target->permissions()->count());
        $audits = DB::table('activity_log')->whereIn('log_name', ['staff_account', 'laboratory_membership'])->get();
        $this->assertCount(2, $audits);
        foreach ($audits as $audit) {
            $properties = json_decode($audit->properties, true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame($target->id, $properties['target_user_id']);
            $this->assertSame($winningActor->id, (int) $audit->causer_id);
            $this->assertSame($winningLab->id, $properties[$audit->log_name === 'staff_account' ? 'origin_lab_id' : 'lab_id']);
        }
        $this->assertSame(3, User::query()->count());
    }

    public static function uniqueAccountIdentifiers(): array
    {
        return [['email'], ['username']];
    }

    #[DataProvider('creationAuthority')]
    public function test_waiting_creation_rechecks_authority_before_any_new_account_is_published(string $authority): void
    {
        $lab = VAPLab::factory()->create();
        $actor = $this->administrator($lab);
        Permission::findOrCreate('add_users', 'web');
        $actor->givePermissionTo('add_users');
        $department = Department::factory()->create();
        $this->assertSame([403], $this->compete([$lab->id], [
            ['actor' => $actor->id, 'lab' => $lab->id, 'create' => true, 'data' => $this->creationPayload('waiting-account@example.test', $department->id)],
        ], function () use ($authority, $lab, $actor): void {
            if ($authority === 'membership') {
                DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $actor->id)->delete();
            } else {
                $actor->removeRole('admin');
            }
        }));
        $this->assertSame(1, User::query()->count());
        $this->assertSame(0, DB::table('department_user')->count());
        $this->assertSame(0, DB::table('activity_log')->whereIn('log_name', ['staff_account', 'laboratory_membership'])->count());
    }

    public static function creationAuthority(): array
    {
        return [['membership'], ['role']];
    }

    public function test_account_creation_and_both_histories_publish_only_at_real_outer_commit(): void
    {
        $lab = VAPLab::factory()->create();
        $actor = $this->administrator($lab);
        Permission::findOrCreate('add_users', 'web');
        $department = Department::factory()->create();
        $payload = $this->creationPayload('committed-account@example.test', $department->id);
        request()->setLaravelSession(app('session')->driver());
        config(['database.connections.staff_creation_observer' => config('database.connections.pgsql')]);
        $observer = DB::connection('staff_creation_observer');
        try {
            foreach ([false, true] as $commit) {
                DB::beginTransaction();
                $target = app(CreateStaffAccount::class)->execute($actor->id, $lab->id, $payload);
                $this->assertFalse($observer->table('users')->where('email', $payload['email'])->exists());
                $this->assertFalse($observer->table('lab_user')->where('user_id', $target->id)->exists());
                $this->assertFalse($observer->table('department_user')->where('user_id', $target->id)->exists());
                $this->assertSame(0, $observer->table('activity_log')->whereIn('log_name', ['staff_account', 'laboratory_membership'])->count());
                if ($commit) {
                    DB::commit();
                } else {
                    DB::rollBack();
                    $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
                    $this->assertSame(0, DB::table('activity_log')->whereIn('log_name', ['staff_account', 'laboratory_membership'])->count());
                }
            }
            $this->assertTrue($observer->table('users')->where('email', $payload['email'])->exists());
            $this->assertSame(1, $observer->table('lab_user')->where('user_id', $target->id)->count());
            $this->assertSame(1, $observer->table('department_user')->where('user_id', $target->id)->count());
            $this->assertSame(2, $observer->table('activity_log')->whereIn('log_name', ['staff_account', 'laboratory_membership'])->count());
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('staff_creation_observer');
        }
    }

    /** @return array<string,mixed> */
    private function creationPayload(string $email, int $departmentId): array
    {
        return ['name' => 'Concurrent new analyst', 'email' => $email, 'username' => 'unique-new-account', 'gender' => 'O',
            'password' => 'Strong-password-123!', 'departments' => [['department_id' => $departmentId]]];
    }

    public function test_reversed_archive_batches_serialize_and_replay_without_duplicate_history(): void
    {
        $lab = VAPLab::factory()->create();
        $actor = $this->administrator($lab);
        $first = $this->member($lab);
        $second = $this->member($lab);
        $this->assertSame([200, 200], $this->compete([$lab->id], [
            ['actor' => $actor->id, 'lab' => $lab->id, 'lifecycle' => 'archive', 'targets' => [$first->id, $second->id]],
            ['actor' => $actor->id, 'lab' => $lab->id, 'lifecycle' => 'archive', 'targets' => [$second->id, $first->id]],
        ]));
        $this->assertSoftDeleted($first);
        $this->assertSoftDeleted($second);
        $this->assertSame(2, DB::table('activity_log')->where('log_name', 'staff_account')->where('event', 'archived')->count());
        $this->assertSame(3, DB::table('lab_user')->where('lab_id', $lab->id)->count());
    }

    public function test_two_labs_setting_the_same_global_status_publish_one_transition(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $actor = $this->administrator($lab);
        $peerActor = $this->administrator($peer);
        $target = $this->member($lab);
        DB::table('lab_user')->insert(['lab_id' => $peer->id, 'user_id' => $target->id]);
        $local = $this->qualification($lab, $target, 'LOCAL-KEEP');
        $foreign = $this->qualification($peer, $target, 'PEER-KEEP');
        $this->assertSame([200, 200], $this->compete([$lab->id, $peer->id], [
            ['actor' => $actor->id, 'lab' => $lab->id, 'lifecycle' => 'status', 'target' => $target->id, 'active' => false],
            ['actor' => $peerActor->id, 'lab' => $peer->id, 'lifecycle' => 'status', 'target' => $target->id, 'active' => false],
        ]));
        $this->assertFalse($target->fresh()->is_active);
        $this->assertSame(1, DB::table('activity_log')->where('log_name', 'staff_account')->where('event', 'deactivated')->count());
        $this->assertSame('LOCAL-KEEP', $local->fresh()->training_reference);
        $this->assertSame('PEER-KEEP', $foreign->fresh()->training_reference);
        $this->assertSame(2, DB::table('lab_user')->where('user_id', $target->id)->count());
    }

    public function test_global_archive_serializes_with_local_membership_removal(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $actor = $this->administrator($lab);
        $remover = $this->member($lab);
        $target = $this->member($lab);
        DB::table('lab_user')->insert(['lab_id' => $peer->id, 'user_id' => $target->id]);
        $this->qualification($lab, $target, 'LOCAL-KEEP');
        $foreign = $this->qualification($peer, $target, 'PEER-KEEP');
        [$archive, $remove] = $this->compete([$lab->id], [
            ['actor' => $actor->id, 'lab' => $lab->id, 'lifecycle' => 'archive', 'targets' => [$target->id]],
            ['actor' => $remover->id, 'lab' => $lab->id, 'target' => $target->id, 'remove' => true, 'data' => []],
        ]);
        $this->assertContains($archive, [200, 404]);
        $this->assertSame(200, $remove);
        $this->assertSame($archive === 200, User::withTrashed()->findOrFail($target->id)->trashed());
        $this->assertDatabaseMissing('lab_user', ['lab_id' => $lab->id, 'user_id' => $target->id]);
        $this->assertDatabaseHas('lab_user', ['lab_id' => $peer->id, 'user_id' => $target->id]);
        $this->assertSame('PEER-KEEP', $foreign->fresh()->training_reference);
        $this->assertSame(2, $target->personnelQualifications()->count());
        $this->assertSame($archive === 200 ? 1 : 0, DB::table('activity_log')->where('log_name', 'staff_account')->count());
        $this->assertSame(1, DB::table('activity_log')->where('log_name', 'laboratory_membership')->count());
    }

    public function test_waiting_global_mutation_rechecks_membership_after_lock_release(): void
    {
        $lab = VAPLab::factory()->create();
        $actor = $this->administrator($lab);
        $target = $this->member($lab);
        $before = $target->fresh()->getAttributes();
        $this->assertSame([403], $this->compete([$lab->id], [
            ['actor' => $actor->id, 'lab' => $lab->id, 'lifecycle' => 'status', 'target' => $target->id, 'active' => false],
        ], fn () => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $actor->id)->delete()));
        $this->assertSame($before, $target->fresh()->getAttributes());
        $this->assertSame(0, DB::table('activity_log')->where('log_name', 'staff_account')->count());
    }

    public function test_global_status_and_history_publish_only_at_real_outer_commit(): void
    {
        $lab = VAPLab::factory()->create();
        $actor = $this->administrator($lab);
        $target = $this->member($lab);
        request()->setLaravelSession(app('session')->driver());
        config(['database.connections.staff_lifecycle_observer' => config('database.connections.pgsql')]);
        $observer = DB::connection('staff_lifecycle_observer');
        try {
            foreach ([false, true] as $commit) {
                DB::beginTransaction();
                app(ManageStaffAccountLifecycle::class)->setStatus($actor->id, $lab->id, $target->id, false);
                $this->assertTrue((bool) $observer->table('users')->where('id', $target->id)->value('is_active'));
                $this->assertSame(0, $observer->table('activity_log')->where('log_name', 'staff_account')->count());
                if ($commit) {
                    DB::commit();
                } else {
                    DB::rollBack();
                    $this->assertTrue($target->fresh()->is_active);
                    $this->assertSame(0, DB::table('activity_log')->where('log_name', 'staff_account')->count());
                }
            }
            $this->assertFalse((bool) $observer->table('users')->where('id', $target->id)->value('is_active'));
            $this->assertSame(1, $observer->table('activity_log')->where('log_name', 'staff_account')->count());
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('staff_lifecycle_observer');
        }
    }

    private function administrator(VAPLab $lab): User
    {
        $actor = $this->member($lab);
        $actor->assignRole(Role::findOrCreate('admin', 'web'));
        Permission::findOrCreate('ban_users', 'web');

        return $actor;
    }

    public function test_dossier_and_both_histories_follow_real_outer_commit_and_rollback(): void
    {
        $lab = VAPLab::factory()->create();
        $actor = $this->member($lab);
        $actor->assignRole(Role::findOrCreate('admin', 'web'));
        $target = $this->member($lab);
        $old = $this->qualification($lab, $target, 'OLD');
        $oldName = $target->name;
        request()->setLaravelSession(app('session')->driver());
        config(['database.connections.staff_history_observer' => config('database.connections.pgsql')]);
        $observer = DB::connection('staff_history_observer');
        $data = ['name' => 'Committed account revision', 'personnel_qualifications' => [['capability' => 'verify_results', 'is_active' => true, 'training_reference' => 'NEW']]];
        try {
            foreach ([false, true] as $commit) {
                DB::beginTransaction();
                app(UpdateStaffAccount::class)->execute($actor->id, $lab->id, $target->id, $data);
                $this->assertSame(2, DB::table('activity_log')->whereIn('log_name', ['staff_account', 'personnel_qualifications'])->count());
                $this->assertSame(0, $observer->table('activity_log')->whereIn('log_name', ['staff_account', 'personnel_qualifications'])->count());
                $this->assertSame($oldName, $observer->table('users')->where('id', $target->id)->value('name'));
                $this->assertSame('OLD', $observer->table('personnel_qualifications')->where('id', $old->id)->value('training_reference'));
                if ($commit) {
                    DB::commit();
                } else {
                    DB::rollBack();
                    $this->assertSame($oldName, $target->fresh()->name);
                    $this->assertModelExists($old);
                    $this->assertSame(0, DB::table('activity_log')->whereIn('log_name', ['staff_account', 'personnel_qualifications'])->count());
                }
            }
            $this->assertSame('Committed account revision', $observer->table('users')->where('id', $target->id)->value('name'));
            $this->assertSame('NEW', $observer->table('personnel_qualifications')->where('user_id', $target->id)->value('training_reference'));
            $this->assertSame(2, $observer->table('activity_log')->whereIn('log_name', ['staff_account', 'personnel_qualifications'])->count());
            $this->assertFalse($observer->table('personnel_qualifications')->where('id', $old->id)->exists());
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('staff_history_observer');
        }
    }

    public function test_two_labs_can_correct_one_shared_dossier_without_overwriting_peer_evidence(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $actor = $this->member($lab);
        $peerActor = $this->member($peer);
        $target = $this->member($lab);
        DB::table('lab_user')->insert(['lab_id' => $peer->id, 'user_id' => $target->id]);
        $local = $this->qualification($lab, $target, 'LOCAL-OLD');
        $foreign = $this->qualification($peer, $target, 'PEER-OLD');
        $before = $target->fresh()->getAttributes();
        $this->assertSame([200, 200], $this->compete([$lab->id, $peer->id], [
            $this->operation($actor, $lab, $target, 'LOCAL-NEW'), $this->operation($peerActor, $peer, $target, 'PEER-NEW'),
        ]));
        $this->assertSame($before, $target->fresh()->getAttributes());
        $this->assertDatabaseMissing('personnel_qualifications', ['id' => $local->id]);
        $this->assertDatabaseMissing('personnel_qualifications', ['id' => $foreign->id]);
        $this->assertSame('LOCAL-NEW', $target->personnelQualifications()->where('lab_id', $lab->id)->sole()->training_reference);
        $this->assertSame('PEER-NEW', $target->personnelQualifications()->where('lab_id', $peer->id)->sole()->training_reference);
        $audits = DB::table('activity_log')->where('log_name', 'personnel_qualifications')->get();
        $this->assertCount(2, $audits);
        foreach ($audits as $audit) {
            $properties = json_decode($audit->properties, true, flags: JSON_THROW_ON_ERROR);
            $this->assertCount(1, $properties['old']);
            $this->assertCount(1, $properties['new']);
            $this->assertSame($properties['lab_id'], $properties['old'][0]['lab_id']);
            $this->assertSame($properties['lab_id'], $properties['new'][0]['lab_id']);
        }
    }

    public function test_membership_removal_serializes_with_dossier_edit_without_changing_peer_data(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $editor = $this->member($lab);
        $remover = $this->member($lab);
        $target = $this->member($lab);
        DB::table('lab_user')->insert(['lab_id' => $peer->id, 'user_id' => $target->id]);
        $this->qualification($lab, $target, 'LOCAL-OLD');
        $foreign = $this->qualification($peer, $target, 'PEER-UNCHANGED');
        $before = $target->fresh()->getAttributes();
        $peerBefore = $foreign->fresh()->getAttributes();
        [$editStatus, $removeStatus] = $this->compete([$lab->id], [$this->operation($editor, $lab, $target, 'LOCAL-NEW'),
            ['actor' => $remover->id, 'lab' => $lab->id, 'target' => $target->id, 'remove' => true, 'data' => []]]);
        $this->assertContains($editStatus, [200, 404]);
        $this->assertSame(200, $removeStatus);
        $this->assertSame($before, $target->fresh()->getAttributes());
        $this->assertSame($peerBefore, $foreign->fresh()->getAttributes());
        $this->assertDatabaseMissing('lab_user', ['lab_id' => $lab->id, 'user_id' => $target->id]);
        $this->assertDatabaseHas('lab_user', ['lab_id' => $peer->id, 'user_id' => $target->id]);
        $this->assertSame($editStatus === 200 ? 'LOCAL-NEW' : 'LOCAL-OLD', $target->personnelQualifications()->where('lab_id', $lab->id)->sole()->training_reference);
        $this->assertSame($editStatus === 200 ? 1 : 0, DB::table('activity_log')->where('log_name', 'personnel_qualifications')->count());
        $this->assertSame(1, DB::table('activity_log')->where('log_name', 'laboratory_membership')->count());
    }

    public function test_opposing_cross_lab_dossier_edits_complete_without_a_user_lock_cycle(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $first = $this->member($lab);
        $second = $this->member($peer);
        DB::table('lab_user')->insert([['lab_id' => $lab->id, 'user_id' => $second->id], ['lab_id' => $peer->id, 'user_id' => $first->id]]);
        $before = DB::table('users')->orderBy('id')->get()->toArray();
        $this->assertSame([200, 200], $this->compete([$lab->id, $peer->id], [
            $this->operation($first, $lab, $second, 'FIRST-LAB'), $this->operation($second, $peer, $first, 'SECOND-LAB'),
        ]));
        $this->assertEquals($before, DB::table('users')->orderBy('id')->get()->toArray());
        $this->assertSame(2, DB::table('personnel_qualifications')->count());
        $this->assertSame(2, DB::table('activity_log')->where('log_name', 'personnel_qualifications')->count());
    }

    private function member(VAPLab $lab): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        foreach (['edit_users', 'delete_users'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        return $user;
    }

    private function qualification(VAPLab $lab, User $target, string $reference): PersonnelQualification
    {
        return PersonnelQualification::query()->create(['lab_id' => $lab->id, 'user_id' => $target->id, 'capability' => 'verify_results', 'is_active' => true, 'training_reference' => $reference]);
    }

    /** @return array{actor:int,lab:int,target:int,remove:bool,data:array<string,mixed>} */
    private function operation(User $actor, VAPLab $lab, User $target, string $reference): array
    {
        return ['actor' => $actor->id, 'lab' => $lab->id, 'target' => $target->id, 'remove' => false,
            'data' => ['personnel_qualifications' => [['capability' => 'verify_results', 'is_active' => true, 'training_reference' => $reference]]]];
    }

    /** @param list<int> $labIds
     * @param  list<array<string,mixed>>  $operations
     * @return list<int>
     */
    private function compete(array $labIds, array $operations, ?\Closure $beforeRelease = null): array
    {
        $connection = DB::connection();
        $barrier = sys_get_temp_dir().'/staff-history-race-'.bin2hex(random_bytes(8));
        mkdir($barrier);
        $processes = [];
        $holding = false;
        $worker = <<<'PHP'
        require getcwd().'/vendor/autoload.php';
        $app = require getcwd().'/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $connection = Illuminate\Support\Facades\DB::connection();
        if (! $app->environment('testing') || $connection->getDatabaseName() !== 'lims_unleashed_test' || $connection->getConfig('search_path') !== $argv[1]) {
            throw new RuntimeException('Dossier concurrency requires its dedicated test schema.');
        }
        request()->setLaravelSession($app->make('session')->driver());
        $connection->beforeExecuting(function (string $query) use ($argv): void {
            if (str_starts_with($query, 'select * from "labs"') && str_contains($query, 'for update')) {
                touch($argv[3].'/boundary-'.$argv[4]);
            }
        });
        $operation = json_decode($argv[2], true, flags: JSON_THROW_ON_ERROR);
        try {
            if ($operation['create'] ?? false) {
                $app->make(App\Actions\CreateStaffAccount::class)->execute($operation['actor'], $operation['lab'], $operation['data']);
            } elseif (isset($operation['lifecycle'])) {
                $lifecycle = $app->make(App\Actions\ManageStaffAccountLifecycle::class);
                if ($operation['lifecycle'] === 'archive') {
                    $lifecycle->archive($operation['actor'], $operation['lab'], $operation['targets'], true);
                } else {
                    $lifecycle->setStatus($operation['actor'], $operation['lab'], $operation['target'], $operation['active']);
                }
            } elseif ($operation['remove']) {
                $app->make(App\Actions\ManageLaboratoryMembership::class)->remove($operation['actor'], $operation['lab'], $operation['target']);
            } else {
                $app->make(App\Actions\UpdateStaffAccount::class)->execute($operation['actor'], $operation['lab'], $operation['target'], $operation['data']);
            }
            echo json_encode(['status' => 200]);
        } catch (Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            echo json_encode(['status' => $exception->getStatusCode()]);
        } catch (Illuminate\Auth\Access\AuthorizationException $exception) {
            echo json_encode(['status' => 403]);
        } catch (Illuminate\Validation\ValidationException $exception) {
            echo json_encode(['status' => 422]);
        }
        PHP;
        $environment = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DATABASE_URL' => '', 'DB_SCHEMA' => $this->schema,
            'DB_HOST' => $connection->getConfig('host'), 'DB_PORT' => (string) $connection->getConfig('port'), 'DB_DATABASE' => $connection->getDatabaseName(),
            'DB_USERNAME' => $connection->getConfig('username'), 'DB_PASSWORD' => $connection->getConfig('password') ?? '',
            'SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array', 'CACHE_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'database', 'MAIL_MAILER' => 'array'];
        try {
            DB::beginTransaction();
            $holding = true;
            DB::table('labs')->whereIn('id', $labIds)->orderBy('id')->lockForUpdate()->get();
            foreach ($operations as $index => $operation) {
                $process = new Process([PHP_BINARY, '-r', $worker, $this->schema, json_encode($operation, JSON_THROW_ON_ERROR), $barrier, (string) $index], base_path(), $environment, timeout: 30);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 15;
            while (count(glob($barrier.'/boundary-*') ?: []) < count($operations) && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertCount(count($operations), glob($barrier.'/boundary-*') ?: [], collect($processes)->map(fn (Process $process): string => $process->getErrorOutput().$process->getOutput())->implode(' | '));
            foreach ($processes as $process) {
                $this->assertTrue($process->isRunning(), $process->getErrorOutput().$process->getOutput());
                $this->assertSame('', trim($process->getOutput()));
            }
            $beforeRelease?->__invoke();
            DB::commit();
            $holding = false;
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
                $results[] = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR)['status'];
            }

            return $results;
        } finally {
            if ($holding) {
                DB::rollBack();
            }
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            foreach (glob($barrier.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($barrier);
        }

    }
}
