<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PublicProposalDecisionConcurrencyTest extends TestCase
{
    #[DataProvider('writeBoundaries')]
    public function test_independent_workers_respect_current_decision_and_token_at_the_write_boundary(array $operations, string $revocation): void
    {
        $connection = DB::connection();
        $this->assertSame('pgsql', $connection->getDriverName());
        $this->assertSame('lims_unleashed_test', $connection->getDatabaseName());
        $this->assertSame(0, $connection->transactionLevel());
        $originalConfig = config('database.connections.pgsql');
        $schema = 'proposal_decision_'.bin2hex(random_bytes(8));
        $barrier = sys_get_temp_dir().'/'.$schema;
        $processes = [];
        $authoring = in_array('send', $operations, true) || in_array('revise', $operations, true);
        mkdir($barrier, 0700);
        DB::statement('CREATE SCHEMA "'.$schema.'"');

        try {
            config(['database.connections.pgsql.search_path' => $schema]);
            DB::purge('pgsql');
            $this->assertSame(0, Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]), Artisan::output());
            $proposal = $this->proposal();
            if ($authoring) {
                $owner = $proposal->user;
                DB::table('lab_user')->insert(['lab_id' => $proposal->lab_id, 'user_id' => $owner->id]);
                $owner->givePermissionTo(Permission::findOrCreate('edit_proposals', 'web'));
                $unit = Unit::create(['code' => 'concurrency-unit', 'description' => 'Concurrency unit']);
                $proposal->items()->create(['item_description' => 'Original', 'qty' => 1, 'unit_price' => 1, 'total' => 1, 'unit_id' => $unit->id]);
                $proposal->update(['status' => in_array('revise', $operations, true) ? 'VIEWED'
                    : (in_array('accept', $operations, true) ? 'REVISED' : 'PENDING')]);
            }
            $before = $proposal->fresh()->getRawOriginal();
            $connection = DB::connection();
            $environment = [
                'APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DATABASE_URL' => '', 'DB_SCHEMA' => $schema,
                'DB_HOST' => $connection->getConfig('host'), 'DB_PORT' => (string) $connection->getConfig('port'),
                'DB_DATABASE' => $connection->getDatabaseName(), 'DB_USERNAME' => $connection->getConfig('username'),
                'DB_PASSWORD' => $connection->getConfig('password') ?? '',
                'CACHE_STORE' => 'array', 'CACHE_DRIVER' => 'array', 'SESSION_DRIVER' => 'array',
                'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => 'array', 'BROADCAST_CONNECTION' => 'null',
            ];
            $worker = <<<'PHP'
            require getcwd().'/vendor/autoload.php';
            $app = require getcwd().'/bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            $connection = Illuminate\Support\Facades\DB::connection();
            if (! $app->environment('testing') || $connection->getDatabaseName() !== 'lims_unleashed_test'
                || $connection->getConfig('search_path') !== $argv[1]) {
                throw new RuntimeException('Public decision concurrency requires its private test schema.');
            }
            Illuminate\Support\Facades\Notification::fake();
            Illuminate\Support\Facades\Queue::fake();
            $bound = App\Models\VAPProposal::findOrFail((int) $argv[2]);
            if ($argv[6] === '1') {
                config(['filesystems.disks.proposal_concurrency' => ['driver' => 'local', 'root' => $argv[4].'/artifacts', 'throw' => true],
                    'filesystems.default' => 'proposal_concurrency']);
                $app->instance(App\Support\ReportStudioPdfRenderer::class, new class extends App\Support\ReportStudioPdfRenderer {
                    public function renderDocument(string $studioType, array $payload, string $filename): array
                    {
                        return ['content' => '%PDF-concurrent', 'renderer' => 'test'];
                    }
                });
                touch($argv[4].'/write-boundary-'.$argv[5]);
                $deadline = microtime(true) + 15;
                while (! is_file($argv[4].'/release') && microtime(true) < $deadline) {
                    usleep(10000);
                }
                if (! is_file($argv[4].'/release')) {
                    throw new RuntimeException('Authoring worker barrier timed out.');
                }
            }
            $connection->beforeExecuting(function (string $query) use ($argv): void {
                if (str_contains($query, 'from "proposals"') && str_contains($query, 'for update')) {
                    touch($argv[4].'/write-boundary-'.$argv[5]);
                }
            });
            try {
                if ($argv[3] === 'send') {
                    $record = $app->make(App\Actions\SendProposal::class)->execute((int) $bound->lab_id, (int) $bound->user_id, $bound);
                } elseif ($argv[3] === 'revise') {
                    $record = $app->make(App\Actions\ReviseProposal::class)->execute((int) $bound->lab_id, (int) $bound->user_id, $bound, [
                        'service_location' => 'Concurrent revision', 'revision_reason' => 'A concurrent documented correction.', 'tolerance_days' => 30,
                        'items' => [['item_description' => 'Replacement', 'qty' => 2, 'unit_price' => 1, 'unit_id' => $bound->items()->sole()->unit_id]],
                    ]);
                } elseif ($argv[3] === 'view') {
                    $record = $app->make(App\Actions\RecordPublicProposalView::class)->execute($bound->unique_hash, '127.0.0.8');
                } else {
                    $accepted = $argv[3] === 'accept';
                    $data = $accepted ? ['confidentiality' => true, 'impartiality' => false, 'nondisclosure' => true]
                        : ['reason' => 'Concurrent client rejection reason'];
                    $record = $app->make(App\Actions\RecordPublicProposalDecision::class)->execute($bound, $accepted, $data, '127.0.0.8');
                }
                echo json_encode(['status' => 200, 'operation' => $argv[3], 'recorded' => $record->status], JSON_THROW_ON_ERROR);
            } catch (Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
                echo json_encode(['status' => 404, 'operation' => $argv[3]], JSON_THROW_ON_ERROR);
            } catch (Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                echo json_encode(['status' => $exception->getStatusCode(), 'operation' => $argv[3]], JSON_THROW_ON_ERROR);
            }
            PHP;

            DB::beginTransaction();
            DB::table('proposals')->where('id', $proposal->id)->lockForUpdate()->first();
            foreach ($operations as $index => $operation) {
                $process = new Process([PHP_BINARY, '-r', $worker, $schema, (string) $proposal->id, $operation, $barrier, (string) $index, $authoring ? '1' : '0'], base_path(), $environment, timeout: 30);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 15;
            while (count(glob($barrier.'/write-boundary-*') ?: []) < count($operations) && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertCount(count($operations), glob($barrier.'/write-boundary-*') ?: [], collect($processes)
                ->map(fn (Process $process): string => $process->getErrorOutput().$process->getOutput())->implode(' | '));
            match ($revocation) {
                'none' => null,
                'token' => DB::table('proposals')->where('id', $proposal->id)->update(['unique_hash' => (string) str()->uuid()]),
                'archive' => DB::table('proposals')->where('id', $proposal->id)->update(['deleted_at' => now()]),
                'state' => DB::table('proposals')->where('id', $proposal->id)->update(['status' => 'EXPIRED']),
            };
            DB::commit();
            if ($authoring) {
                touch($barrier.'/release');
            }

            $outcomes = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
                $outcomes[] = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);
            }
            $decisions = array_values(array_filter($outcomes, fn (array $outcome): bool => $outcome['operation'] !== 'view'));
            $successful = array_values(array_filter($decisions, fn (array $outcome): bool => $outcome['status'] === 200));
            if ($authoring) {
                $publicDecision = in_array('accept', $operations, true);
                $staffOperation = in_array('send', $operations, true) ? 'send' : 'revise';
                $staffSuccesses = array_filter($outcomes, fn (array $outcome): bool => $outcome['operation'] === $staffOperation && $outcome['status'] === 200);
                $this->assertLessThanOrEqual(1, count($staffSuccesses));
                if ($revocation !== 'none') {
                    $this->assertCount(0, $successful);
                    $this->assertSame(array_fill(0, count($operations), $revocation === 'state' ? 409 : 404), array_column($outcomes, 'status'));
                } elseif ($publicDecision) {
                    $this->assertSame('ACCEPTED', $proposal->fresh()->status);
                    $this->assertSame(1, $proposal->complianceAgreementLogs()->count());
                    $this->assertSame(1, Activity::where('subject_id', $proposal->id)->where('description', 'accepted')->count());
                } else {
                    $this->assertCount(1, $staffSuccesses);
                    $this->assertSame($staffOperation === 'send' ? 'SENT' : 'REVISED', $proposal->fresh()->status);
                    $statuses = array_column($outcomes, 'status');
                    sort($statuses);
                    $this->assertSame([200, 409, 409, 409], $statuses);
                }
                $this->assertSame(count($staffSuccesses), Activity::where('subject_id', $proposal->id)->where('description', $staffOperation === 'send' ? 'sent' : 'revised')->count());
                $this->assertSame(1, $proposal->items()->count());
                $this->assertSame($staffOperation === 'revise' ? 1 + count($staffSuccesses) : 1, $proposal->items()->withTrashed()->count());
                $disk = Storage::build(['driver' => 'local', 'root' => $barrier.'/artifacts', 'throw' => true]);
                $this->assertCount($staffOperation === 'send' ? count($staffSuccesses) : 0, $disk->allFiles());
                if ($proposal->fresh()->file_path) {
                    $this->assertSame('%PDF-concurrent', $disk->get($proposal->fresh()->file_path));
                }
                foreach (['lab_id', 'customer_id', 'warehouse_id', 'proposal_no', 'details'] as $field) {
                    $this->assertSame($before[$field], $proposal->fresh()->getRawOriginal($field));
                }
            } elseif ($revocation === 'none') {
                $this->assertCount(1, $successful);
                $statuses = array_column($decisions, 'status');
                sort($statuses);
                $this->assertSame([200, ...array_fill(0, count($decisions) - 1, 400)], $statuses);
                $accepted = $successful[0]['operation'] === 'accept';
                $this->assertSame($accepted ? 'ACCEPTED' : 'REJECTED', $proposal->fresh()->status);
                $this->assertSame(1, $proposal->complianceAgreement()->count());
                $this->assertSame($accepted ? 1 : 0, $proposal->complianceAgreementLogs()->count());
                $this->assertSame($accepted, $proposal->complianceAgreement()->sole()->nondisclosure);
                $this->assertSame(1, Activity::where('subject_id', $proposal->id)->whereIn('description', ['accepted', 'rejected'])->count());
                if (in_array('view', $operations, true)) {
                    $this->assertSame([200], array_column(array_filter($outcomes, fn (array $outcome): bool => $outcome['operation'] === 'view'), 'status'));
                    $this->assertLessThanOrEqual(1, Activity::where('subject_id', $proposal->id)->where('description', 'viewed_by_client')->count());
                }
                foreach (['lab_id', 'customer_id', 'warehouse_id', 'unique_hash', 'proposal_no', 'details'] as $field) {
                    $this->assertSame($before[$field], $proposal->fresh()->getRawOriginal($field));
                }
            } else {
                $this->assertCount(0, $successful);
                $this->assertSame(array_fill(0, count($operations), $revocation === 'state' ? 400 : 404), array_column($outcomes, 'status'));
                $this->assertSame($revocation === 'state' ? 'EXPIRED' : 'SENT', $proposal->fresh()->status);
                $this->assertSame(0, $proposal->complianceAgreement()->count());
                $this->assertSame(0, $proposal->complianceAgreementLogs()->count());
                $this->assertSame(0, Activity::where('subject_id', $proposal->id)->whereIn('description', ['accepted', 'rejected'])->count());
            }
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            while (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::statement('DROP SCHEMA "'.$schema.'" CASCADE');
            config(['database.connections.pgsql' => $originalConfig]);
            DB::purge('pgsql');
            File::deleteDirectory($barrier);
        }
    }

    public static function writeBoundaries(): array
    {
        return [
            'accept replay' => [['accept', 'accept', 'accept', 'accept'], 'none'],
            'reject replay' => [['reject', 'reject', 'reject', 'reject'], 'none'],
            'competing decisions' => [['accept', 'reject', 'accept', 'reject'], 'none'],
            'view and competing decisions' => [['view', 'accept', 'reject', 'accept'], 'none'],
            'rotated token after binding' => [['accept', 'reject', 'accept', 'reject'], 'token'],
            'archived after binding' => [['accept', 'reject', 'accept', 'reject'], 'archive'],
            'expired after binding' => [['accept', 'reject', 'accept', 'reject'], 'state'],
            'staff send replay' => [['send', 'send', 'send', 'send'], 'none'],
            'staff revision replay' => [['revise', 'revise', 'revise', 'revise'], 'none'],
            'revision and acceptance' => [['revise', 'accept', 'revise', 'accept'], 'none'],
            'send and acceptance' => [['send', 'accept', 'send', 'accept'], 'none'],
            'staff rotated token after binding' => [['send', 'send', 'send', 'send'], 'token'],
            'staff archived after binding' => [['revise', 'revise', 'revise', 'revise'], 'archive'],
            'staff expired after binding' => [['send', 'send', 'send', 'send'], 'state'],
        ];
    }

    private function proposal(): VAPProposal
    {
        $owner = User::factory()->create();
        $lab = VAPLab::factory()->create();
        $customer = Customer::create(['name' => fake()->company()]);
        $site = Warehouse::create(['name' => fake()->company(), 'customer_id' => $customer->id]);
        $template = VAPProposalTemplate::create(['name' => 'Concurrent decision fixture', 'content' => '<p>Decision</p>', 'user_id' => $owner->id]);
        $proposal = new VAPProposal([
            'proposal_no' => 'CONCURRENT-'.str()->uuid(), 'proposal_year' => now()->year,
            'customer_id' => $customer->id, 'warehouse_id' => $site->id, 'department_id' => Department::factory()->create()->id,
            'user_id' => $owner->id, 'template_id' => $template->id, 'status' => 'SENT',
            'unique_hash' => (string) str()->uuid(), 'details' => ['evidence' => 'retained'], 'sub_total' => 0, 'total' => 0,
        ]);
        $proposal->lab_id = $lab->id;
        $proposal->save();

        return $proposal;
    }
}
