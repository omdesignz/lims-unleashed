<?php

namespace Tests\Feature;

use App\Models\AnalysisCategory;
use App\Models\CollectionProduct;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Matrix;
use App\Models\Product;
use App\Models\Profile;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class LaboratoryIntakeSynchronizationConcurrencyTest extends TestCase
{
    #[DataProvider('collectionTypes')]
    public function test_parallel_stale_intakes_commit_one_graph_and_one_set_of_numbers(string $type): void
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
        $lab = VAPLab::factory()->create();
        $department = Department::factory()->create();
        $customer = Customer::query()->create(['name' => 'Concurrent intake customer '.Str::uuid()]);
        $warehouse = Warehouse::query()->create(['name' => 'Concurrent intake site', 'customer_id' => $customer->id]);
        $category = AnalysisCategory::query()->create(['name' => 'Concurrent intake category', 'code' => Str::uuid(), 'department_id' => $department->id]);
        $matrix = Matrix::query()->create(['code' => Str::uuid(), 'description' => 'Concurrent intake matrix']);
        $profile = Profile::query()->create(['name' => 'Concurrent intake profile', 'code' => Str::uuid(), 'category_id' => $category->id]);
        $matrix->profiles()->attach($profile);
        $product = Product::query()->create(['name' => 'Concurrent intake product', 'matrix_id' => $matrix->id]);
        $entry = VAPSampleEntry::factory()->create([
            'code' => null, 'name' => 'Concurrent intake', 'lab_id' => $lab->id, 'department_id' => $department->id,
            'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id,
            'client_submitted_info' => ['collection_type' => $type, 'product_id' => $product->id, 'requested_profile_ids' => [$profile->id]],
        ]);
        $barrier = sys_get_temp_dir().'/laboratory-intake-'.Str::uuid();
        mkdir($barrier, 0700);
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
        Illuminate\Support\Carbon::setTestNow($argv[2]);
        $entry = App\Models\VAPSampleEntry::findOrFail($argv[1]);
        if ($entry->collection_product_id !== null) {
            throw new RuntimeException('Workers must load the same unlinked intake before the barrier.');
        }
        touch($argv[3].'/ready-'.$argv[4]);
        $deadline = microtime(true) + 10;
        while (! is_file($argv[3].'/start')) {
            if (microtime(true) > $deadline) {
                throw new RuntimeException('Timed out at the concurrency barrier.');
            }
            usleep(10000);
        }
        $record = $app->make(App\Support\SampleEntryCollectionFlowService::class)->sync($entry);
        echo json_encode(['collection_id' => $record->id, 'lab_code_id' => $record->code->id,
            'sample_ids' => $record->code->samples()->pluck('id')->all()], JSON_THROW_ON_ERROR);
        PHP;
        $processes = [];

        try {
            for ($index = 0; $index < 4; $index++) {
                $process = new Process([PHP_BINARY, '-r', $worker, (string) $entry->id, $date, $barrier, (string) $index], base_path(), $environment, timeout: 20);
                $process->start();
                $processes[] = $process;
            }

            $deadline = microtime(true) + 10;
            while (count(glob($barrier.'/ready-*')) < 4 && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertCount(4, glob($barrier.'/ready-*'), 'Every worker must hold a stale model before writes begin.');
            touch($barrier.'/start');
            $results = [];

            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
                $results[] = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);
            }

            $this->assertCount(1, array_unique(array_column($results, 'collection_id')));
            $this->assertSame(array_fill(0, 4, $results[0]), $results);
            $this->assertSame($results[0]['collection_id'], $entry->fresh()->collection_product_id);
            $this->assertSame($type, $entry->fresh()->collectionProduct->collection->collectionable_type);
            $this->assertSame(1, CollectionProduct::query()->where('extra_data->sample_entry_id', $entry->id)->count());
            $this->assertSame(1, DB::table('lab_codes')->where('cl_month', $month)->count());
            $this->assertSame(1, DB::table('samples')->where('sample_month', $month)->count());
            $this->assertSame(1, DB::table('analysis')->where('cl_id', $results[0]['lab_code_id'])->count());
            $this->assertSame(1, (int) DB::table('sequence_counters')->where('scope_values->cl_month', $month)->value('last_value'));
            $this->assertSame(1, (int) DB::table('sequence_counters')->where('scope_values->sample_month', $month)->value('last_value'));
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }

            foreach (glob($barrier.'/*') as $file) {
                unlink($file);
            }
            rmdir($barrier);
            $records = DB::table('collection_product')->where('extra_data->sample_entry_id', $entry->id)->get();
            $codeIds = DB::table('lab_codes')->whereIn('collection_id', $records->pluck('id'))->pluck('id');
            $subjects = DB::table('collections')->whereIn('id', $records->pluck('collection_id'))->pluck('collectionable_id');
            DB::table('sample_entries')->where('id', $entry->id)->delete();
            DB::table('analysis')->whereIn('cl_id', $codeIds)->delete();
            DB::table('samples')->whereIn('cl_id', $codeIds)->delete();
            DB::table('lab_codes')->whereIn('id', $codeIds)->delete();
            DB::table('activity_log')->where('subject_type', (new CollectionProduct)->getMorphClass())->whereIn('subject_id', $records->pluck('id'))->delete();
            DB::table('collection_product')->whereIn('id', $records->pluck('id'))->delete();
            DB::table('collections')->whereIn('id', $records->pluck('collection_id'))->delete();
            DB::table($type.'_collections')->whereIn('id', $subjects)->delete();
            DB::table('products')->where('id', $product->id)->delete();
            $matrix->profiles()->detach();
            DB::table('profiles')->where('id', $profile->id)->delete();
            DB::table('matrixes')->where('id', $matrix->id)->delete();
            DB::table('analysis_categories')->where('id', $category->id)->delete();
            DB::table('warehouses')->where('id', $warehouse->id)->delete();
            DB::table('customers')->where('id', $customer->id)->delete();
            DB::table('departments')->where('id', $department->id)->delete();
            DB::table('labs')->where('id', $lab->id)->delete();
            DB::table('sequence_counters')->where('scope_values->cl_month', $month)
                ->orWhere('scope_values->sample_month', $month)
                ->orWhere('scope_values->lab_id', (string) $lab->id)->delete();
            $this->travelBack();
        }
    }

    /** @return array<string, array{string}> */
    public static function collectionTypes(): array
    {
        return ['direct' => ['direct'], 'programmed' => ['programmed']];
    }
}
