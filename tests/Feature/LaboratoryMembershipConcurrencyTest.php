<?php

namespace Tests\Feature;

use App\Actions\ManageLaboratoryMembership;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\IsolatedPostgresTestCase;

class LaboratoryMembershipConcurrencyTest extends IsolatedPostgresTestCase
{
    private function member(VAPLab $lab): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        foreach (['add_users', 'delete_users'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        return $user;
    }

    public function test_four_native_joiners_create_one_membership_and_one_audit(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $target = $this->member($peer);
        $before = $target->fresh()->getAttributes();
        $operations = [];
        for ($index = 0; $index < 4; $index++) {
            $operations[] = ['actor' => $this->member($lab)->id, 'lab' => $lab->id, 'email' => $target->email];
        }
        $this->assertSame([200, 200, 200, 200], $this->compete([$lab->id], $operations));
        $this->assertSame(1, DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $target->id)->count());
        $this->assertSame(1, DB::table('activity_log')->where('log_name', 'laboratory_membership')->count());
        $this->assertSame($before, $target->fresh()->getAttributes());
        $this->assertDatabaseHas('lab_user', ['lab_id' => $peer->id, 'user_id' => $target->id]);
    }

    public function test_opposing_cross_lab_actor_target_pairs_do_not_deadlock_or_change_global_accounts(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $first = $this->member($lab);
        $second = $this->member($peer);
        $before = DB::table('users')->orderBy('id')->get()->toArray();
        $this->assertSame([200, 200], $this->compete([$lab->id, $peer->id], [
            ['actor' => $first->id, 'lab' => $lab->id, 'email' => $second->email],
            ['actor' => $second->id, 'lab' => $peer->id, 'email' => $first->email],
        ]));
        $this->assertEquals($before, DB::table('users')->orderBy('id')->get()->toArray());
        $this->assertSame(4, DB::table('lab_user')->count());
        $this->assertSame(2, DB::table('activity_log')->where('log_name', 'laboratory_membership')->count());
    }

    public function test_membership_and_audit_follow_the_real_outer_commit_or_rollback(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $actor = $this->member($lab);
        $target = $this->member($peer);
        request()->setLaravelSession(app('session')->driver());
        config(['database.connections.membership_observer' => config('database.connections.pgsql')]);
        $observer = DB::connection('membership_observer');
        try {
            DB::beginTransaction();
            app(ManageLaboratoryMembership::class)->join($actor->id, $lab->id, $target->email);
            $this->assertSame(0, $observer->table('lab_user')->where('lab_id', $lab->id)->where('user_id', $target->id)->count());
            $this->assertSame(0, $observer->table('activity_log')->where('log_name', 'laboratory_membership')->count());
            DB::rollBack();
            $this->assertSame(0, DB::table('activity_log')->where('log_name', 'laboratory_membership')->count());
            DB::beginTransaction();
            app(ManageLaboratoryMembership::class)->join($actor->id, $lab->id, $target->email);
            DB::commit();
            $this->assertSame(1, $observer->table('lab_user')->where('lab_id', $lab->id)->where('user_id', $target->id)->count());
            $this->assertSame(1, $observer->table('activity_log')->where('log_name', 'laboratory_membership')->count());
            DB::beginTransaction();
            app(ManageLaboratoryMembership::class)->remove($actor->id, $lab->id, $target->id);
            $this->assertSame(1, $observer->table('lab_user')->where('lab_id', $lab->id)->where('user_id', $target->id)->count());
            $this->assertSame(1, $observer->table('activity_log')->where('log_name', 'laboratory_membership')->count());
            DB::rollBack();
            $this->assertSame(1, DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $target->id)->count());
            DB::beginTransaction();
            app(ManageLaboratoryMembership::class)->remove($actor->id, $lab->id, $target->id);
            DB::commit();
            $this->assertSame(0, $observer->table('lab_user')->where('lab_id', $lab->id)->where('user_id', $target->id)->count());
            $this->assertSame(2, $observer->table('activity_log')->where('log_name', 'laboratory_membership')->count());
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('membership_observer');
        }
    }

    /** @param list<int> $labIds
     * @param  list<array{actor:int,lab:int,email:string}>  $operations
     * @return list<int>
     */
    private function compete(array $labIds, array $operations): array
    {
        $connection = DB::connection();
        $barrier = sys_get_temp_dir().'/membership-race-'.bin2hex(random_bytes(8));
        mkdir($barrier);
        $processes = [];
        $holding = false;
        $worker = <<<'PHP'
        require getcwd().'/vendor/autoload.php';
        $app = require getcwd().'/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $connection = Illuminate\Support\Facades\DB::connection();
        if (! $app->environment('testing') || $connection->getDatabaseName() !== 'lims_unleashed_test' || $connection->getConfig('search_path') !== $argv[1]) {
            throw new RuntimeException('Membership concurrency requires its dedicated test schema.');
        }
        request()->setLaravelSession($app->make('session')->driver());
        $connection->beforeExecuting(function (string $query) use ($argv): void {
            if (str_starts_with($query, 'select * from "labs"') && str_contains($query, 'for update')) {
                touch($argv[3].'/boundary-'.$argv[4]);
            }
        });
        $operation = json_decode($argv[2], true, flags: JSON_THROW_ON_ERROR);
        $app->make(App\Actions\ManageLaboratoryMembership::class)->join($operation['actor'], $operation['lab'], $operation['email']);
        echo json_encode(['status' => 200]);
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
