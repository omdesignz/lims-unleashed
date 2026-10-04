<?php

namespace Tests\Feature;

use App\Actions\SaveGeneralSettings;
use App\Models\Permission;
use App\Models\User;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\IsolatedPostgresTestCase;

class GeneralSettingsConcurrencyTest extends IsolatedPostgresTestCase
{
    #[DataProvider('outcomes')]
    public function test_waiting_save_preserves_the_latest_committed_omitted_values(bool $commit): void
    {
        $actor = $this->operator();
        $before = (new GeneralSettings)->toArray();
        $this->assertSame($commit ? 422 : 200, $this->waitingSave($actor, function () use ($actor): void {
            app(SaveGeneralSettings::class)->handle($actor->id, ['settings_revision' => (new GeneralSettings)->revision(), 'app_contact' => 'Committed contact', 'app_private_key' => 'FICTIONAL-CONCURRENT-KEY']);
        }, $commit));
        $settings = new GeneralSettings;
        $this->assertSame($commit ? $before['app_slogan'] : 'Worker slogan', $settings->app_slogan);
        $this->assertSame($commit ? 'Committed contact' : $before['app_contact'], $settings->app_contact);
        $this->assertSame($commit ? 'FICTIONAL-CONCURRENT-KEY' : $before['app_private_key'], $settings->app_private_key);
    }

    /** @return array<string,array{bool}> */
    public static function outcomes(): array
    {
        return ['first writer commits' => [true], 'first writer rolls back' => [false]];
    }

    #[DataProvider('revocations')]
    public function test_waiting_save_rechecks_current_authority(string $revocation): void
    {
        $actor = $this->operator();
        $before = (new GeneralSettings)->toArray();
        $this->assertSame(403, $this->waitingSave($actor, function () use ($actor, $revocation): void {
            match ($revocation) {
                'permission' => $actor->revokePermissionTo('edit_settings'),
                'active' => $actor->update(['is_active' => false]),
                'verified' => $actor->update(['email_verified_at' => null]),
            };
        }, true));
        $this->assertSame($before, (new GeneralSettings)->toArray());
    }

    /** @return array<string,array{string}> */
    public static function revocations(): array
    {
        return ['permission' => ['permission'], 'active account' => ['active'], 'verified account' => ['verified']];
    }

    public function test_independent_reader_sees_changes_only_after_real_outer_commit(): void
    {
        $actor = $this->operator();
        $before = DB::table('settings')->where('group', 'general')->where('name', 'app_name')->value('payload');
        config(['database.connections.settings_observer' => config('database.connections.pgsql')]);
        $observer = DB::connection('settings_observer');
        try {
            foreach ([false, true] as $commit) {
                DB::beginTransaction();
                app(SaveGeneralSettings::class)->handle($actor->id, ['settings_revision' => (new GeneralSettings)->revision(), 'app_name' => 'Outer transaction settings']);
                $this->assertSame($before, $observer->table('settings')->where('group', 'general')->where('name', 'app_name')->value('payload'));
                $commit ? DB::commit() : DB::rollBack();
                $this->assertSame($commit ? json_encode('Outer transaction settings') : $before,
                    $observer->table('settings')->where('group', 'general')->where('name', 'app_name')->value('payload'));
            }
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('settings_observer');
        }
    }

    private function operator(): User
    {
        $actor = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $actor->givePermissionTo(Permission::findOrCreate('edit_settings', 'web'));

        return $actor;
    }

    private function waitingSave(User $actor, callable $firstOperation, bool $commit): int
    {
        $db = DB::connection();
        $barrier = sys_get_temp_dir().'/settings-race-'.bin2hex(random_bytes(8));
        mkdir($barrier, 0700);
        $worker = <<<'PHP'
            require getcwd().'/vendor/autoload.php';
            $app = require getcwd().'/bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            $db = Illuminate\Support\Facades\DB::connection();
            if (! $app->environment('testing') || $db->getDatabaseName() !== 'lims_unleashed_test' || $db->getConfig('search_path') !== $argv[1]) {
                throw new RuntimeException('Settings concurrency requires its isolated test schema.');
            }
            $revision = app(App\Settings\GeneralSettings::class)->revision();
            App\Models\User::findOrFail((int) $argv[3])->can('edit_settings');
            $pid = $db->selectOne('SELECT pg_backend_pid() AS pid')->pid;
            $db->beforeExecuting(function (string $query) use ($argv, $pid): void {
                if (str_contains($query, '"settings"') && str_contains($query, 'for update')) {
                    file_put_contents($argv[2].'/boundary', (string) $pid);
                }
            });
            try {
                app(App\Actions\SaveGeneralSettings::class)->handle((int) $argv[3], ['settings_revision' => $revision, 'app_slogan' => 'Worker slogan']);
                echo '200';
            } catch (Illuminate\Validation\ValidationException $exception) {
                if (! array_key_exists('settings_revision', $exception->errors())) throw $exception;
                echo '422';
            } catch (Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                echo $exception->getStatusCode();
            }
            PHP;
        $environment = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DATABASE_URL' => '', 'DB_SCHEMA' => $this->schema,
            'DB_HOST' => $db->getConfig('host'), 'DB_PORT' => (string) $db->getConfig('port'),
            'DB_DATABASE' => $db->getDatabaseName(), 'DB_USERNAME' => $db->getConfig('username'),
            'DB_PASSWORD' => $db->getConfig('password') ?? '', 'CACHE_STORE' => 'array', 'CACHE_DRIVER' => 'array'];
        $process = new Process([PHP_BINARY, '-r', $worker, $this->schema, $barrier, (string) $actor->id], base_path(), $environment, timeout: 30);
        try {
            DB::beginTransaction();
            DB::table('settings')->where('group', 'general')->orderBy('id')->lockForUpdate()->get();
            $process->start();
            $deadline = microtime(true) + 15;
            while (! is_file($barrier.'/boundary') && microtime(true) < $deadline && $process->isRunning()) {
                usleep(10000);
            }
            $this->assertFileExists($barrier.'/boundary', $process->getErrorOutput().$process->getOutput());
            $pid = (int) file_get_contents($barrier.'/boundary');
            do {
                DB::select('SELECT pg_stat_clear_snapshot()');
                $waiting = DB::table('pg_stat_activity')->where('pid', $pid)->where('wait_event_type', 'Lock')->exists();
                if (! $waiting) {
                    usleep(10000);
                }
            } while (! $waiting && microtime(true) < $deadline);
            $this->assertTrue($waiting, 'The settings worker must actually wait on the held PostgreSQL lock.');
            $firstOperation();
            $commit ? DB::commit() : DB::rollBack();
            $process->wait();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());

            return (int) trim($process->getOutput());
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            if ($process->isRunning()) {
                $process->stop();
            }
            foreach (glob($barrier.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($barrier);
        }
    }
}
