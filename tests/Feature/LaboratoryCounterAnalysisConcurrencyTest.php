<?php

namespace Tests\Feature;

use App\Models;
use App\Support\SampleEntryCollectionFlowService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class LaboratoryCounterAnalysisConcurrencyTest extends TestCase
{
    #[DataProvider('boundaryChanges')]
    public function test_parallel_registration_reuses_one_counter_and_rechecks_the_write_boundary(string $change): void
    {
        $connection = DB::connection();
        $this->assertSame('pgsql', $connection->getDriverName());
        $this->assertSame('lims_unleashed_test', $connection->getDatabaseName());
        Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);

        do {
            $date = random_int(8100, 8199).'-'.str_pad((string) random_int(1, 12), 2, '0', STR_PAD_LEFT).'-15 12:00:00';
            $month = date('y/m', strtotime($date));
        } while (DB::table('lab_codes')->where('cl_month', $month)->exists()
            || DB::table('samples')->where('sample_month', $month)->exists()
            || DB::table('sequence_counters')->where('scope_values->cl_month', $month)->exists()
            || DB::table('sequence_counters')->where('scope_values->sample_month', $month)->exists());

        $this->travelTo($date);
        $permissionExisted = Models\Permission::query()->where('name', 'add_counter_analysis')->where('guard_name', 'web')->exists();
        $fixtures = null;
        $processes = [];
        $holdingAccessLock = false;
        $barrier = sys_get_temp_dir().'/counter-registration-'.Str::uuid();
        mkdir($barrier, 0700);

        try {
            $fixtures = DB::transaction(function () use ($change): array {
                $operator = Models\User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
                $permission = Models\Permission::findOrCreate('add_counter_analysis', 'web');
                $operator->givePermissionTo($permission);
                $lab = Models\VAPLab::factory()->create();
                DB::table('lab_user')->insert(['user_id' => $operator->id, 'lab_id' => $lab->id]);
                $department = Models\Department::factory()->create();
                $customer = Models\Customer::query()->create(['name' => 'Concurrent counter customer '.Str::uuid()]);
                $warehouse = Models\Warehouse::query()->create(['name' => 'Concurrent counter site '.Str::uuid(), 'customer_id' => $customer->id]);
                $category = Models\AnalysisCategory::query()->create(['name' => 'Concurrent counter category', 'code' => Str::uuid(), 'department_id' => $department->id]);
                $matrix = Models\Matrix::query()->create(['code' => Str::uuid(), 'description' => 'Concurrent counter matrix']);
                $profile = Models\Profile::query()->create(['name' => 'Concurrent counter profile', 'code' => Str::uuid(), 'category_id' => $category->id]);
                $matrix->profiles()->attach($profile);
                $parameter = Models\Parameter::query()->create(['name' => 'Concurrent counter parameter', 'code' => Str::uuid(), 'active' => true]);
                $profile->parameters()->attach($parameter);
                $product = Models\Product::query()->create(['name' => 'Concurrent counter product', 'matrix_id' => $matrix->id]);
                $entry = Models\VAPSampleEntry::factory()->create([
                    'code' => null, 'name' => 'Concurrent counter intake', 'lab_id' => $lab->id, 'department_id' => $department->id,
                    'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id,
                    'client_submitted_info' => ['collection_type' => 'direct', 'product_id' => $product->id, 'requested_profile_ids' => [$profile->id]],
                ]);
                $accession = app(SampleEntryCollectionFlowService::class)->sync($entry);
                $collection = $accession->collection;
                $code = $accession->code;
                $sample = $code->samples()->firstOrFail();
                $analysis = Models\Analysis::query()->where('sample_id', $sample->id)->firstOrFail();
                $result = Models\Result::query()->create([
                    'sample_id' => $sample->id, 'parameter_id' => $parameter->id, 'profile_id' => $profile->id,
                    'code_id' => $code->id, 'collection_id' => $accession->id, 'product_id' => $product->id,
                    'resultable_id' => $analysis->id, 'resultable_type' => $analysis->getMorphClass(),
                    'inserted_value' => '0', 'inserted_by_id' => $operator->id, 'inserted_by' => $operator->name,
                    'inserted_date' => now(), 'requested_counter_analysis' => false,
                ]);

                $records = compact('operator', 'permission', 'lab', 'department', 'customer', 'warehouse', 'category', 'matrix', 'profile', 'parameter', 'product', 'entry', 'accession', 'collection', 'code', 'sample', 'analysis', 'result');
                if ($change === 'different operators') {
                    for ($index = 1; $index < 4; $index++) {
                        $member = Models\User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
                        $member->givePermissionTo($permission);
                        DB::table('lab_user')->insert(['user_id' => $member->id, 'lab_id' => $lab->id]);
                        $records['additional_operator_'.$index] = $member;
                    }
                }

                return $records;
            });
            ['operator' => $operator, 'lab' => $lab, 'entry' => $entry, 'accession' => $accession,
                'code' => $code, 'sample' => $sample, 'analysis' => $analysis, 'result' => $result] = $fixtures;
            $operators = array_values(array_filter($fixtures, fn (Model $fixture): bool => $fixture instanceof Models\User));
            $operatorIds = array_map(fn (Models\User $member): int => $member->id, $operators);
            $beforeCounters = $this->scopedCounters($month, $lab->id);
            $environment = [
                'APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql',
                'DB_HOST' => $connection->getConfig('host'), 'DB_PORT' => (string) $connection->getConfig('port'),
                'DB_DATABASE' => $connection->getDatabaseName(), 'DB_USERNAME' => $connection->getConfig('username'),
                'DB_PASSWORD' => $connection->getConfig('password') ?? '', 'DATABASE_URL' => '',
                'CACHE_STORE' => 'array', 'CACHE_DRIVER' => 'array', 'SESSION_DRIVER' => 'array',
                'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => 'array',
            ];
            $worker = <<<'PHP'
            require getcwd().'/vendor/autoload.php';
            $app = require getcwd().'/bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            if (! $app->environment('testing') || Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'lims_unleashed_test') {
                throw new RuntimeException('Concurrency test requires its dedicated test database.');
            }
            Illuminate\Support\Facades\Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);
            Illuminate\Support\Facades\Notification::fake();
            Illuminate\Support\Carbon::setTestNow($argv[4]);
            $job = unserialize(serialize(new App\Jobs\RegisterCounterAnalysis((int) $argv[1], (int) $argv[2], (int) $argv[3])));
            Illuminate\Support\Facades\DB::connection()->beforeExecuting(function (string $query) use ($argv): void {
                if (str_contains($query, 'from "labs"') && str_contains($query, 'for update')) {
                    touch($argv[5].'/write-boundary-'.$argv[6]);
                }
            });
            try {
                $job->handle($app->make(App\Actions\RequestLaboratoryCounterAnalysis::class));
                $counter = App\Models\CounterAnalysis::query()->where('result_id', $argv[1])->firstOrFail();
                $output = ['status' => 200, 'counter_id' => $counter->id];
            } catch (Illuminate\Auth\Access\AuthorizationException) {
                $output = ['status' => 403];
            } catch (Illuminate\Database\Eloquent\ModelNotFoundException) {
                $output = ['status' => 404];
            }
            echo json_encode($output, JSON_THROW_ON_ERROR);
            PHP;

            DB::beginTransaction();
            $holdingAccessLock = true;
            DB::table('labs')->where('id', $lab->id)->lockForUpdate()->first();
            for ($index = 0; $index < 4; $index++) {
                $workerOperator = $operators[$index % count($operators)];
                $process = new Process([
                    PHP_BINARY, '-r', $worker, (string) $result->id, (string) $workerOperator->id,
                    (string) $lab->id, $date, $barrier, (string) $index,
                ], base_path(), $environment, timeout: 30);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 15;
            while (count(glob($barrier.'/write-boundary-*')) < 4 && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertCount(4, glob($barrier.'/write-boundary-*'), 'Every deserialized worker must reach the fresh-access lock before mutation. '.collect($processes)
                ->map(fn (Process $process): string => $process->getErrorOutput().$process->getOutput())->implode(' | '));

            match ($change) {
                'membership' => DB::table('lab_user')->where('user_id', $operator->id)->where('lab_id', $lab->id)->delete(),
                'permission' => DB::table('model_has_permissions')->where('model_type', $operator->getMorphClass())->where('model_id', $operator->id)->delete(),
                'inactive user' => DB::table('users')->where('id', $operator->id)->update(['is_active' => false]),
                'unverified user' => DB::table('users')->where('id', $operator->id)->update(['email_verified_at' => null]),
                'archived lab' => DB::table('labs')->where('id', $lab->id)->update(['deleted_at' => now()]),
                'archived intake' => DB::table('sample_entries')->where('id', $entry->id)->update(['deleted_at' => now()]),
                'archived accession' => DB::table('collection_product')->where('id', $accession->id)->update(['deleted_at' => now()]),
                'archived code' => DB::table('lab_codes')->where('id', $code->id)->update(['deleted_at' => now()]),
                'archived sample' => DB::table('samples')->where('id', $sample->id)->update(['deleted_at' => now()]),
                'archived analysis' => DB::table('analysis')->where('id', $analysis->id)->update(['deleted_at' => now()]),
                'archived result' => DB::table('results')->where('id', $result->id)->update(['deleted_at' => now()]),
                default => null,
            };
            DB::commit();
            $holdingAccessLock = false;
            $outcomes = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
                $outcomes[] = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);
            }

            $counters = Models\CounterAnalysis::withTrashed()->where('result_id', $result->id)->get();
            if (! in_array($change, ['none', 'different operators'], true)) {
                $expectedStatus = in_array($change, ['membership', 'permission', 'inactive user', 'unverified user', 'archived lab'], true) ? 403 : 404;
                $this->assertSame(array_fill(0, 4, $expectedStatus), array_column($outcomes, 'status'));
                $this->assertCount(0, $counters);
                $this->assertFalse(Models\Result::withTrashed()->findOrFail($result->id)->requested_counter_analysis);
                $this->assertSame($beforeCounters, $this->scopedCounters($month, $lab->id));
                $this->assertSame(1, DB::table('samples')->where('sample_month', $month)->count());
                $this->assertSame(1, DB::table('lab_codes')->where('cl_month', $month)->count());

                return;
            }

            $this->assertSame(array_fill(0, 4, 200), array_column($outcomes, 'status'));
            $this->assertCount(1, $counters);
            $counter = $counters->first();
            $this->assertSame(array_fill(0, 4, $counter->id), array_column($outcomes, 'counter_id'));
            $this->assertSame($analysis->id, $counter->analysis_id);
            $this->assertContains($counter->user_id, $operatorIds);
            $this->assertSame($accession->id, $counter->code->collection_id);
            $this->assertSame($sample->id, data_get($counter->extra_data, 'source_sample_id'));
            $this->assertNotSame($sample->id, $counter->sample_id);
            $this->assertTrue($result->fresh()->requested_counter_analysis);
            $this->assertSame(2, DB::table('samples')->where('sample_month', $month)->count());
            $this->assertSame(2, DB::table('lab_codes')->where('cl_month', $month)->count());
            $this->assertSame(1, (int) DB::table('sequence_counters')->where('scope_values->cl_month', $month)
                ->where('scope_values->codeable_type', 'analysis')->value('last_value'));
            $this->assertSame(1, (int) DB::table('sequence_counters')->where('scope_values->cl_month', $month)
                ->where('scope_values->codeable_type', 'counteranalysis')->value('last_value'));
            $this->assertSame(2, (int) DB::table('sequence_counters')->where('scope_values->sample_month', $month)->value('last_value'));
            $this->assertSame(1, (int) DB::table('sequence_counters')->where('scope_values->lab_id', (string) $lab->id)->value('last_value'));
            $this->assertSame(1, (int) $code->fresh()->seq);
            $this->assertSame(1, (int) $counter->code->seq);
            $this->assertSame(1, (int) $sample->fresh()->seq);
            $this->assertSame(2, (int) $counter->sample->seq);
            $this->assertSame('0', $result->fresh()->inserted_value);
        } finally {
            if ($holdingAccessLock) {
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
            if ($fixtures) {
                $this->removeFixtures($fixtures, $month, $permissionExisted);
            }
            $this->travelBack();
        }
    }

    /** @return array<string, array{string}> */
    public static function boundaryChanges(): array
    {
        return collect(['none', 'different operators', 'membership', 'permission', 'inactive user', 'unverified user', 'archived lab',
            'archived intake', 'archived accession', 'archived code', 'archived sample', 'archived analysis', 'archived result'])
            ->mapWithKeys(fn (string $change): array => [$change => [$change]])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function scopedCounters(string $month, int $labId): array
    {
        return DB::table('sequence_counters')->where('scope_values->cl_month', $month)
            ->orWhere('scope_values->sample_month', $month)->orWhere('scope_values->lab_id', (string) $labId)
            ->orderBy('scope_hash')->get()->map(fn (object $counter): array => get_object_vars($counter))->all();
    }

    /** @param array<string, Model> $fixtures */
    private function removeFixtures(array $fixtures, string $month, bool $permissionExisted): void
    {
        ['operator' => $operator, 'permission' => $permission, 'lab' => $lab, 'department' => $department,
            'customer' => $customer, 'warehouse' => $warehouse, 'category' => $category, 'matrix' => $matrix,
            'profile' => $profile, 'parameter' => $parameter, 'product' => $product, 'entry' => $entry,
            'accession' => $accession, 'collection' => $collection] = $fixtures;
        $codeIds = DB::table('lab_codes')->where('collection_id', $accession->id)->pluck('id');
        $sampleIds = DB::table('samples')->whereIn('cl_id', $codeIds)->pluck('id');
        DB::table('counter_analysis')->whereIn('cl_id', $codeIds)->delete();
        DB::table('results')->whereIn('sample_id', $sampleIds)->delete();
        DB::table('analysis')->whereIn('cl_id', $codeIds)->delete();
        DB::table('samples')->whereIn('id', $sampleIds)->delete();
        DB::table('lab_codes')->whereIn('id', $codeIds)->delete();
        DB::table('sample_entries')->where('id', $entry->id)->delete();
        DB::table('collection_product')->where('id', $accession->id)->delete();
        DB::table('collections')->where('id', $collection->id)->delete();
        DB::table('direct_collections')->where('id', $collection->collectionable_id)->delete();
        foreach ($fixtures as $fixture) {
            $morphType = array_search($fixture::class, Relation::morphMap(), true);
            if ($morphType !== false) {
                DB::table('activity_log')->where('subject_type', $morphType)->where('subject_id', $fixture->id)->delete();
            }
        }
        $operators = array_filter($fixtures, fn (Model $fixture): bool => $fixture instanceof Models\User);
        foreach ($operators as $member) {
            DB::table('activity_log')->where('causer_type', $member->getMorphClass())->where('causer_id', $member->id)->delete();
        }
        DB::table('products')->where('id', $product->id)->delete();
        $matrix->profiles()->detach();
        $profile->parameters()->detach();
        DB::table('parameters')->where('id', $parameter->id)->delete();
        DB::table('profiles')->where('id', $profile->id)->delete();
        DB::table('matrixes')->where('id', $matrix->id)->delete();
        DB::table('analysis_categories')->where('id', $category->id)->delete();
        DB::table('warehouses')->where('id', $warehouse->id)->delete();
        DB::table('customers')->where('id', $customer->id)->delete();
        DB::table('departments')->where('id', $department->id)->delete();
        DB::table('lab_user')->where('lab_id', $lab->id)->delete();
        DB::table('labs')->where('id', $lab->id)->delete();
        foreach ($operators as $member) {
            DB::table('model_has_permissions')->where('model_type', $member->getMorphClass())->where('model_id', $member->id)->delete();
            DB::table('users')->where('id', $member->id)->delete();
        }
        if (! $permissionExisted) {
            DB::table('permissions')->where('id', $permission->id)->delete();
        }
        DB::table('sequence_counters')->where('scope_values->cl_month', $month)
            ->orWhere('scope_values->sample_month', $month)->orWhere('scope_values->lab_id', (string) $lab->id)->delete();
    }
}
