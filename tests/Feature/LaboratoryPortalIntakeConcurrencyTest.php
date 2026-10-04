<?php

namespace Tests\Feature;

use App\Models\AnalysisCategory;
use App\Models\CollectionProduct;
use App\Models\Customer;
use App\Models\CustomerRequest;
use App\Models\Department;
use App\Models\Matrix;
use App\Models\Permission;
use App\Models\PersonnelQualification;
use App\Models\Product;
use App\Models\Profile;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class LaboratoryPortalIntakeConcurrencyTest extends TestCase
{
    #[DataProvider('intakeScenarios')]
    public function test_concurrent_requests_recheck_access_before_issuing_portal_rows(string $type, string $revocation): void
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
        $permissionExisted = Permission::query()->where('name', 'add_samples')->where('guard_name', 'web')->exists();
        $fixtures = DB::transaction(function (): array {
            $operator = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
            $permission = Permission::findOrCreate('add_samples', 'web');
            $operator->givePermissionTo($permission);
            $lab = VAPLab::factory()->create();
            DB::table('lab_user')->insert(['user_id' => $operator->id, 'lab_id' => $lab->id]);
            $department = Department::factory()->create();
            $qualification = PersonnelQualification::query()->create([
                'lab_id' => $lab->id,
                'user_id' => $operator->id, 'qualified_by_id' => $operator->id, 'department_id' => $department->id,
                'capability' => 'sample_intake_validation', 'authorized_from' => now()->subDay(),
                'authorized_until' => now()->addYear(), 'training_completed_at' => now()->subDay(),
                'training_reference' => 'PORTAL-CONCURRENCY', 'is_active' => true,
            ]);
            $customer = Customer::query()->create(['name' => 'Concurrent portal customer '.Str::uuid()]);
            $warehouse = Warehouse::query()->create(['name' => 'Concurrent portal site '.Str::uuid(), 'customer_id' => $customer->id]);
            $otherCustomer = Customer::query()->create(['name' => 'Other concurrent portal customer '.Str::uuid()]);
            $otherWarehouse = Warehouse::query()->create(['name' => 'Other concurrent portal site '.Str::uuid(), 'customer_id' => $otherCustomer->id]);
            $category = AnalysisCategory::query()->create(['name' => 'Concurrent portal category', 'code' => Str::uuid(), 'department_id' => $department->id]);
            $matrix = Matrix::query()->create(['code' => Str::uuid(), 'description' => 'Concurrent portal matrix']);
            $profile = Profile::query()->create(['name' => 'Concurrent portal profile', 'code' => Str::uuid(), 'category_id' => $category->id]);
            $matrix->profiles()->attach($profile);
            $product = Product::query()->create(['name' => 'Concurrent portal product', 'matrix_id' => $matrix->id]);
            $source = CustomerRequest::query()->create([
                'lab_id' => $lab->id,
                'reference' => 'CONCURRENT-PORTAL-'.Str::uuid(), 'title' => 'Concurrent source row',
                'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id,
                'request_type' => 'analysis_request', 'status' => 'pending',
                'extra_data' => [
                    'product_id' => $product->id, 'matrix_id' => $matrix->id, 'requested_profiles' => [$profile->id],
                    'samples' => [['batch_index' => 7, 'sample_name' => 'Only portal row', 'lot' => 'CONCURRENT-LOT']],
                ],
            ]);

            return compact('operator', 'permission', 'lab', 'department', 'qualification', 'customer', 'warehouse', 'otherCustomer', 'otherWarehouse', 'category', 'matrix', 'profile', 'product', 'source');
        });
        [
            'operator' => $operator, 'permission' => $permission, 'lab' => $lab, 'department' => $department,
            'qualification' => $qualification, 'customer' => $customer, 'warehouse' => $warehouse,
            'otherCustomer' => $otherCustomer, 'otherWarehouse' => $otherWarehouse,
            'category' => $category, 'matrix' => $matrix, 'profile' => $profile, 'product' => $product, 'source' => $source,
        ] = $fixtures;
        $payload = [
            'name' => 'Concurrent portal sample', 'sample_type' => 'ROTINA', 'lab_id' => $lab->id,
            'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id, 'department_id' => $department->id,
            'received_at' => now()->toDateTimeString(), 'status' => 'POR_INICIAR', 'portal_request_id' => $source->id,
            'client_submitted_info' => [
                'product_id' => $product->id, 'matrix_id' => $matrix->id, 'requested_profile_ids' => [$profile->id],
                'collection_type' => $type, 'request_origin' => 'client', 'batch_sample_index' => 7,
            ],
        ];
        $barrier = sys_get_temp_dir().'/portal-intake-'.Str::uuid();
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
        Illuminate\Support\Facades\Notification::fake();
        Illuminate\Support\Carbon::setTestNow($argv[3]);
        Illuminate\Support\Facades\Auth::guard('web')->setUser(App\Models\User::findOrFail($argv[1]));
        Illuminate\Support\Facades\DB::connection()->beforeExecuting(function (string $query) use ($argv): void {
            if (str_contains($query, 'from "labs"') && str_contains($query, 'for update')) {
                touch($argv[4].'/write-boundary-'.$argv[5]);
            }
        });
        $payload = json_decode($argv[2], true, flags: JSON_THROW_ON_ERROR);
        $mode = (int) $argv[5] % 2 === 0 ? 'single' : 'manual';
        $url = route($mode === 'single' ? 'vap_samples.samples.store' : 'vap_samples.samples.bulk-store');
        $request = Illuminate\Http\Request::create($url, 'POST', $mode === 'single' ? $payload : ['samples' => [$payload]]);
        $request->headers->set('Accept', 'application/json');
        $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
        $response = $kernel->handle($request);
        $body = json_decode($response->getContent(), true);
        $kernel->terminate($request, $response);
        echo json_encode(['status' => $response->getStatusCode(), 'errors' => $body['errors'] ?? []], JSON_THROW_ON_ERROR);
        PHP;
        $processes = [];
        $holdingAccessLock = false;

        try {
            DB::beginTransaction();
            $holdingAccessLock = true;
            DB::table('labs')->where('id', $lab->id)->lockForUpdate()->first();

            for ($index = 0; $index < 4; $index++) {
                $process = new Process([
                    PHP_BINARY, '-r', $worker, (string) $operator->id, json_encode($payload, JSON_THROW_ON_ERROR),
                    $date, $barrier, (string) $index,
                ], base_path(), $environment, timeout: 30);
                $process->start();
                $processes[] = $process;
            }

            $deadline = microtime(true) + 15;
            while (count(glob($barrier.'/write-boundary-*')) < 4 && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertCount(4, glob($barrier.'/write-boundary-*'), 'Every HTTP worker must finish validation and reach the fresh-access lock before writes begin. '.collect($processes)
                ->map(fn (Process $process): string => $process->getErrorOutput().$process->getOutput())->implode(' | '));

            match ($revocation) {
                'membership' => DB::table('lab_user')->where('user_id', $operator->id)->where('lab_id', $lab->id)->delete(),
                'permission' => DB::table('model_has_permissions')->where('model_type', $operator->getMorphClass())->where('model_id', $operator->id)->delete(),
                'active user' => DB::table('users')->where('id', $operator->id)->update(['is_active' => false]),
                'unverified user' => DB::table('users')->where('id', $operator->id)->update(['email_verified_at' => null]),
                'archived lab' => DB::table('labs')->where('id', $lab->id)->update(['deleted_at' => now()]),
                'qualification' => DB::table('personnel_qualifications')->where('id', $qualification->id)->update(['is_active' => false]),
                'archived source' => DB::table('customer_requests')->where('id', $source->id)->update(['deleted_at' => now()]),
                'completed source' => DB::table('customer_requests')->where('id', $source->id)->update(['status' => 'completed']),
                'source customer' => DB::table('customer_requests')->where('id', $source->id)->update(['customer_id' => $otherCustomer->id]),
                'source site' => DB::table('customer_requests')->where('id', $source->id)->update(['warehouse_id' => $otherWarehouse->id]),
                default => null,
            };
            DB::commit();
            $holdingAccessLock = false;
            $results = [];

            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
                $results[] = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);
            }

            if ($revocation !== 'none') {
                $expectedStatus = in_array($revocation, ['archived source', 'completed source', 'source customer', 'source site'], true) ? 422 : 403;
                $this->assertSame(array_fill(0, 4, $expectedStatus), collect($results)->pluck('status')->sort()->values()->all());
                if ($expectedStatus === 422) {
                    foreach ($results as $result) {
                        $this->assertArrayHasKey('portal_request_id', $result['errors']);
                    }
                }
                $this->assertSame(0, VAPSampleEntry::query()->where('lab_id', $lab->id)->count());
                $storedSource = CustomerRequest::withTrashed()->findOrFail($source->id);
                $this->assertNull(data_get($storedSource->extra_data, 'validated_sample_entry_ids'));
                $this->assertNull(data_get($storedSource->extra_data, 'validated_batch_indexes'));
                $this->assertSame(0, DB::table('lab_codes')->where('cl_month', $month)->count());
                $this->assertSame(0, DB::table('samples')->where('sample_month', $month)->count());
                $this->assertSame(0, DB::table('sequence_counters')->where('scope_values->lab_id', (string) $lab->id)->count());

                return;
            }

            $this->assertSame([302, 422, 422, 422], collect($results)->pluck('status')->sort()->values()->all());
            foreach (collect($results)->where('status', 422) as $result) {
                $this->assertArrayHasKey('client_submitted_info.batch_sample_index', $result['errors']);
            }
            $entries = VAPSampleEntry::query()->where('lab_id', $lab->id)->get();
            $this->assertCount(1, $entries);
            $entry = $entries->first();
            $this->assertSame('CONCURRENT-LOT', data_get($entry->client_submitted_info, 'lot'));
            $this->assertSame($source->id, $entry->customer_request_id);
            $this->assertSame($type, $entry->collectionProduct->collection->collectionable_type);
            $this->assertSame([$entry->id], data_get($source->fresh()->extra_data, 'validated_sample_entry_ids'));
            $this->assertSame([7], data_get($source->fresh()->extra_data, 'validated_batch_indexes'));
            $this->assertSame(1, CollectionProduct::query()->where('extra_data->sample_entry_id', $entry->id)->count());
            $this->assertSame(1, DB::table('lab_codes')->where('cl_month', $month)->count());
            $this->assertSame(1, DB::table('samples')->where('sample_month', $month)->count());
            $this->assertSame(1, DB::table('analysis')->where('cl_id', $entry->collectionProduct->code->id)->count());
            $this->assertSame(1, (int) DB::table('sequence_counters')->where('scope_values->cl_month', $month)->value('last_value'));
            $this->assertSame(1, (int) DB::table('sequence_counters')->where('scope_values->sample_month', $month)->value('last_value'));
            $this->assertSame(1, (int) DB::table('sequence_counters')->where('scope_values->lab_id', (string) $lab->id)->value('last_value'));
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
            $entries = DB::table('sample_entries')->where('lab_id', $lab->id)->get();
            $records = DB::table('collection_product')->whereIn('id', $entries->pluck('collection_product_id')->filter())->get();
            $codeIds = DB::table('lab_codes')->whereIn('collection_id', $records->pluck('id'))->pluck('id');
            $sampleIds = DB::table('samples')->whereIn('cl_id', $codeIds)->pluck('id');
            $subjects = DB::table('collections')->whereIn('id', $records->pluck('collection_id'))->pluck('collectionable_id');
            DB::table('sample_entries')->whereIn('id', $entries->pluck('id'))->delete();
            DB::table('results')->whereIn('sample_id', $sampleIds)->delete();
            DB::table('analysis')->whereIn('cl_id', $codeIds)->delete();
            DB::table('samples')->whereIn('id', $sampleIds)->delete();
            DB::table('lab_codes')->whereIn('id', $codeIds)->delete();
            DB::table('activity_log')->where('causer_type', $operator->getMorphClass())->where('causer_id', $operator->id)->delete();
            DB::table('activity_log')->where('subject_type', (new CollectionProduct)->getMorphClass())->whereIn('subject_id', $records->pluck('id'))->delete();
            DB::table('collection_product')->whereIn('id', $records->pluck('id'))->delete();
            DB::table('collections')->whereIn('id', $records->pluck('collection_id'))->delete();
            DB::table($type.'_collections')->whereIn('id', $subjects)->delete();
            DB::table('customer_requests')->where('id', $source->id)->delete();
            DB::table('products')->where('id', $product->id)->delete();
            $matrix->profiles()->detach();
            DB::table('profiles')->where('id', $profile->id)->delete();
            DB::table('matrixes')->where('id', $matrix->id)->delete();
            DB::table('analysis_categories')->where('id', $category->id)->delete();
            DB::table('warehouses')->where('id', $warehouse->id)->delete();
            DB::table('customers')->where('id', $customer->id)->delete();
            DB::table('warehouses')->where('id', $otherWarehouse->id)->delete();
            DB::table('customers')->where('id', $otherCustomer->id)->delete();
            DB::table('personnel_qualifications')->where('id', $qualification->id)->delete();
            DB::table('model_has_permissions')->where('model_type', $operator->getMorphClass())->where('model_id', $operator->id)->delete();
            DB::table('lab_user')->where('user_id', $operator->id)->delete();
            DB::table('users')->where('id', $operator->id)->delete();
            DB::table('departments')->where('id', $department->id)->delete();
            DB::table('labs')->where('id', $lab->id)->delete();
            if (! $permissionExisted) {
                DB::table('permissions')->where('id', $permission->id)->delete();
            }
            DB::table('sequence_counters')->where('scope_values->cl_month', $month)
                ->orWhere('scope_values->sample_month', $month)
                ->orWhere('scope_values->lab_id', (string) $lab->id)->delete();
            $this->travelBack();
        }
    }

    /** @return array<string, array{string, string}> */
    public static function intakeScenarios(): array
    {
        $scenarios = [];
        foreach (['direct', 'programmed'] as $type) {
            foreach (['none', 'membership', 'permission', 'active user', 'unverified user', 'archived lab', 'qualification', 'archived source', 'completed source', 'source customer', 'source site'] as $revocation) {
                $scenarios[$type.' / '.$revocation] = [$type, $revocation];
            }
        }

        return $scenarios;
    }
}
