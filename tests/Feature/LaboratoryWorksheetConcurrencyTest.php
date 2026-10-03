<?php

namespace Tests\Feature;

use App\Actions\CreateAnalysisWorksheet;
use App\Actions\PrepareSampleEntryPayload;
use App\Models;
use App\Support\SampleEntryCollectionFlowService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class LaboratoryWorksheetConcurrencyTest extends TestCase
{
    #[DataProvider('writeBoundaries')]
    public function test_parallel_mutations_reuse_one_workbook_and_recheck_access_at_the_write_boundary(string $operation, string $change): void
    {
        $connection = DB::connection();
        $this->assertSame('pgsql', $connection->getDriverName());
        $this->assertSame('lims_unleashed_test', $connection->getDatabaseName());
        Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);
        Notification::fake();
        do {
            $date = random_int(8300, 8399).'-'.str_pad((string) random_int(1, 12), 2, '0', STR_PAD_LEFT).'-15 12:00:00';
            $month = date('y/m', strtotime($date));
        } while (DB::table('lab_codes')->where('cl_month', $month)->exists()
            || DB::table('samples')->where('sample_month', $month)->exists()
            || DB::table('sequence_counters')->where('scope_values->cl_month', $month)->exists()
            || DB::table('sequence_counters')->where('scope_values->sample_month', $month)->exists());
        $this->travelTo($date);
        $abilities = ['view_worksheets', 'add_worksheets', 'delete_worksheets', 'restore_worksheets'];
        $existingPermissionIds = Models\Permission::query()->whereIn('name', $abilities)->where('guard_name', 'web')->pluck('id')->all();
        $fixtures = null;
        $processes = [];
        $holdingLock = false;
        $barrier = sys_get_temp_dir().'/worksheet-concurrency-'.Str::uuid();
        mkdir($barrier, 0700);

        try {
            $fixtures = DB::transaction(fn (): array => $this->fixture($abilities));
            $operators = collect($fixtures)->filter(fn (Model $model): bool => $model instanceof Models\User)->values();
            $operatorIds = $operators->pluck('id')->all();
            $worksheet = null;
            if ($operation !== 'generate') {
                $worksheet = app(CreateAnalysisWorksheet::class)->execute($fixtures['lab']->id, $operators[0]->id, $fixtures['analysis']->id);
                if ($operation === 'restore') {
                    $worksheet->delete();
                }
            }
            $before = $worksheet?->fresh()->getAttributes();
            $identity = collect(['entry', 'accession', 'code', 'sample', 'analysis'])
                ->mapWithKeys(fn (string $key): array => [$key => $fixtures[$key]->fresh()->getAttributes()]);
            $counterBefore = $this->counters($month, $fixtures['lab']->id);
            $auditBefore = $this->auditCount($fixtures['analysis']->id);
            $environment = [
                'APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DATABASE_URL' => '',
                'DB_HOST' => $connection->getConfig('host'), 'DB_PORT' => (string) $connection->getConfig('port'),
                'DB_DATABASE' => $connection->getDatabaseName(), 'DB_USERNAME' => $connection->getConfig('username'),
                'DB_PASSWORD' => $connection->getConfig('password') ?? '',
                'CACHE_STORE' => 'array', 'CACHE_DRIVER' => 'array', 'SESSION_DRIVER' => 'array',
                'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => 'array',
            ];
            $worker = <<<'PHP'
            require getcwd().'/vendor/autoload.php';
            $app = require getcwd().'/bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            if (! $app->environment('testing') || Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'lims_unleashed_test') {
                throw new RuntimeException('Worksheet concurrency requires its dedicated test database.');
            }
            Illuminate\Support\Facades\Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);
            Illuminate\Support\Facades\Notification::fake();
            Illuminate\Support\Carbon::setTestNow($argv[6]);
            Illuminate\Support\Facades\DB::connection()->beforeExecuting(function (string $query) use ($argv): void {
                if (str_contains($query, 'from "labs"') && str_contains($query, 'for update')) {
                    touch($argv[7].'/write-boundary-'.$argv[8]);
                }
            });
            try {
                if ($argv[1] === 'generate') {
                    $worksheet = $app->make(App\Actions\CreateAnalysisWorksheet::class)->execute((int) $argv[2], (int) $argv[3], (int) $argv[4]);
                    $output = ['status' => 200, 'worksheet_id' => $worksheet->id];
                } else {
                    $changed = $app->make(App\Actions\SetWorksheetArchived::class)->execute((int) $argv[2], (int) $argv[3], [(int) $argv[5]], $argv[1] === 'archive');
                    $output = ['status' => 200, 'worksheet_id' => (int) $argv[5], 'changed' => $changed];
                }
            } catch (Illuminate\Auth\Access\AuthorizationException) {
                $output = ['status' => 403];
            }
            echo json_encode($output, JSON_THROW_ON_ERROR);
            PHP;
            DB::beginTransaction();
            $holdingLock = true;
            DB::table('labs')->where('id', $fixtures['lab']->id)->lockForUpdate()->first();
            for ($index = 0; $index < 4; $index++) {
                $process = new Process([
                    PHP_BINARY, '-r', $worker, $operation, (string) $fixtures['lab']->id, (string) $operators[$index]->id,
                    (string) $fixtures['analysis']->id, (string) ($worksheet?->id ?? 0), $date, $barrier, (string) $index,
                ], base_path(), $environment, timeout: 30);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 15;
            while (count(glob($barrier.'/write-boundary-*')) < 4 && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertCount(4, glob($barrier.'/write-boundary-*'), 'All workers must reach the fresh-access boundary. '.collect($processes)
                ->map(fn (Process $process): string => $process->getErrorOutput().$process->getOutput())->implode(' | '));
            if ($change === 'membership') {
                DB::table('lab_user')->where('lab_id', $fixtures['lab']->id)->whereIn('user_id', $operatorIds)->delete();
            } elseif ($change === 'permission') {
                DB::table('model_has_permissions')->where('model_type', $operators[0]->getMorphClass())->whereIn('model_id', $operatorIds)->delete();
            }
            DB::commit();
            $holdingLock = false;
            $outcomes = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
                $outcomes[] = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);
            }
            if ($change !== 'none') {
                $this->assertSame(array_fill(0, 4, 403), array_column($outcomes, 'status'));
                $this->assertSame($before, $worksheet?->fresh()->getAttributes());
                $this->assertSame($auditBefore, $this->auditCount($fixtures['analysis']->id));
                $this->assertSame($worksheet ? 1 : 0, Models\Worksheet::withTrashed()->where('analysis_id', $fixtures['analysis']->id)->count());
            } else {
                $this->assertSame(array_fill(0, 4, 200), array_column($outcomes, 'status'));
                $this->assertCount(1, array_unique(array_column($outcomes, 'worksheet_id')));
                $record = Models\Worksheet::withTrashed()->where('analysis_id', $fixtures['analysis']->id)->sole();
                $this->assertSame($operation === 'archive', $record->trashed());
                $this->assertSame($auditBefore + 1, $this->auditCount($fixtures['analysis']->id));
                if ($operation !== 'generate') {
                    $this->assertSame(1, array_sum(array_column($outcomes, 'changed')));
                    $this->assertSame($before['worksheets'], $record->getAttributes()['worksheets']);
                    $this->assertSame($before['user_id'], $record->user_id);
                } else {
                    $this->assertContains($record->user_id, $operatorIds);
                }
            }
            foreach ($identity as $key => $attributes) {
                $this->assertSame($attributes, $fixtures[$key]->fresh()->getAttributes());
            }
            $this->assertEquals($counterBefore, $this->counters($month, $fixtures['lab']->id));
        } finally {
            if ($holdingLock) {
                DB::rollBack();
            }
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            foreach (glob($barrier.'/*') as $file) {
                unlink($file);
            }
            rmdir($barrier);
            if ($fixtures !== null) {
                $this->cleanUpFixture($fixtures, $month, $abilities, $existingPermissionIds);
            }
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $this->travelBack();
        }
    }

    /** @return array<string, array{string, string}> */
    public static function writeBoundaries(): array
    {
        $cases = [];
        foreach (['generate', 'archive', 'restore'] as $operation) {
            foreach (['none', 'membership', 'permission'] as $change) {
                $cases[$operation.' '.$change] = [$operation, $change];
            }
        }

        return $cases;
    }

    /** @param list<string> $abilities @return array<string, Model> */
    private function fixture(array $abilities): array
    {
        $lab = Models\VAPLab::factory()->create();
        $department = Models\Department::factory()->create();
        $customer = Models\Customer::query()->create(['name' => 'Concurrent worksheet customer '.Str::uuid()]);
        $warehouse = Models\Warehouse::query()->create(['name' => 'Concurrent worksheet site '.Str::uuid(), 'customer_id' => $customer->id]);
        $category = Models\AnalysisCategory::query()->create(['name' => 'Concurrent worksheet category '.Str::uuid(), 'code' => Str::uuid(), 'department_id' => $department->id]);
        $matrix = Models\Matrix::query()->create(['code' => Str::uuid(), 'description' => 'Concurrent worksheet matrix']);
        $profile = Models\Profile::query()->create(['name' => 'Concurrent worksheet profile', 'code' => Str::uuid(), 'category_id' => $category->id]);
        $matrix->profiles()->attach($profile);
        $parameter = Models\Parameter::query()->create(['name' => 'Concurrent worksheet parameter', 'code' => Str::uuid(), 'active' => true]);
        $profile->parameters()->attach($parameter);
        $product = Models\Product::query()->create(['name' => 'Concurrent worksheet product', 'matrix_id' => $matrix->id]);
        $payload = app(PrepareSampleEntryPayload::class)->execute([
            'lab_id' => $lab->id, 'department_id' => $department->id, 'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id,
            'client_submitted_info' => ['product_id' => $product->id, 'requested_profile_ids' => [$profile->id], 'collection_type' => 'direct'],
        ], null);
        $entry = Models\VAPSampleEntry::factory()->create($payload);
        $accession = app(SampleEntryCollectionFlowService::class)->sync($entry);
        $collection = $accession->collection;
        $subject = $collection->collectionable;
        $code = $accession->code;
        $sample = $code->samples()->firstOrFail();
        $analysis = $sample->analysis;
        $fixtures = compact('lab', 'department', 'customer', 'warehouse', 'category', 'matrix', 'profile', 'parameter', 'product', 'entry', 'accession', 'collection', 'subject', 'code', 'sample', 'analysis');
        for ($index = 0; $index < 4; $index++) {
            $operator = Models\User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
            foreach ($abilities as $ability) {
                $operator->givePermissionTo(Models\Permission::findOrCreate($ability, 'web'));
            }
            DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $operator->id]);
            $fixtures['operator_'.$index] = $operator;
        }

        return $fixtures;
    }

    private function auditCount(int $analysisId): int
    {
        return DB::table('activity_log')->where('subject_type', (new Models\Worksheet)->getMorphClass())
            ->whereIn('subject_id', Models\Worksheet::withTrashed()->where('analysis_id', $analysisId)->select('id'))->count();
    }

    /** @return array<int, object> */
    private function counters(string $month, int $labId): array
    {
        return DB::table('sequence_counters')->where('scope_values->cl_month', $month)
            ->orWhere('scope_values->sample_month', $month)->orWhere('scope_values->lab_id', (string) $labId)
            ->orderBy('scope_hash')->get()->toArray();
    }

    /** @param array<string, Model> $fixtures @param list<string> $abilities @param list<int> $existingPermissionIds */
    private function cleanUpFixture(array $fixtures, string $month, array $abilities, array $existingPermissionIds): void
    {
        if (! app()->environment('testing') || DB::connection()->getDatabaseName() !== 'lims_unleashed_test') {
            throw new \RuntimeException('Fixture cleanup requires the dedicated test database.');
        }
        DB::transaction(function () use ($fixtures, $month, $abilities, $existingPermissionIds): void {
            $worksheetIds = Models\Worksheet::withTrashed()->where('analysis_id', $fixtures['analysis']->id)->pluck('id');
            DB::table('activity_log')->where('subject_type', (new Models\Worksheet)->getMorphClass())->whereIn('subject_id', $worksheetIds)->delete();
            DB::table('worksheets')->whereIn('id', $worksheetIds)->delete();
            $operators = collect($fixtures)->filter(fn (Model $fixture): bool => $fixture instanceof Models\User);
            DB::table('model_has_permissions')->where('model_type', $operators->first()->getMorphClass())->whereIn('model_id', $operators->pluck('id'))->delete();
            DB::table('lab_user')->where('lab_id', $fixtures['lab']->id)->whereIn('user_id', $operators->pluck('id'))->delete();
            $fixtures['profile']->parameters()->detach();
            $fixtures['matrix']->profiles()->detach();
            foreach (['entry', 'analysis', 'sample', 'code', 'accession', 'collection', 'subject', 'product', 'parameter', 'profile', 'matrix', 'category', 'warehouse', 'customer', 'department', 'lab'] as $key) {
                $model = $fixtures[$key];
                $types = array_filter([$model::class, array_search($model::class, Relation::morphMap(), true)]);
                DB::table('activity_log')->whereIn('subject_type', $types)->where('subject_id', $model->id)->delete();
                DB::table($model->getTable())->where('id', $model->id)->delete();
            }
            DB::table('users')->whereIn('id', $operators->pluck('id'))->delete();
            DB::table('permissions')->whereIn('name', $abilities)->where('guard_name', 'web')->whereNotIn('id', $existingPermissionIds)->delete();
            DB::table('sequence_counters')->where('scope_values->cl_month', $month)
                ->orWhere('scope_values->sample_month', $month)->orWhere('scope_values->lab_id', (string) $fixtures['lab']->id)->delete();
        });
    }
}
