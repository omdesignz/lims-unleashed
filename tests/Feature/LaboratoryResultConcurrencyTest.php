<?php

namespace Tests\Feature;

use App\Actions\PrepareSampleEntryPayload;
use App\Actions\ProcessLaboratoryResults;
use App\Actions\RequestLaboratoryCounterAnalysis;
use App\Models;
use App\Support\SampleEntryCollectionFlowService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\Process\Process;
use Tests\IsolatedPostgresTestCase;

class LaboratoryResultConcurrencyTest extends IsolatedPostgresTestCase
{
    private const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9sX6lz4AAAAASUVORK5CYII=';

    private const ALTERNATE_SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    private ?string $mediaRoot = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mediaRoot = sys_get_temp_dir().'/result-stage-files-'.$this->schema;
        File::ensureDirectoryExists($this->mediaRoot, 0700);
        config(['filesystems.disks.public.root' => $this->mediaRoot, 'media-library.disk_name' => 'public']);
        Storage::forgetDisk('public');
    }

    protected function tearDown(): void
    {
        try {
            if ($this->mediaRoot !== null) {
                File::deleteDirectory($this->mediaRoot);
                Storage::forgetDisk('public');
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_four_native_replays_publish_one_result_and_one_stage_audit(): void
    {
        [$operator, $lab, $entry, $product, $roots] = $this->fixture();
        $operation = $this->operation($operator, $lab, $roots->first());
        $this->assertSame([200, 200, 200, 200], $this->compete($lab, array_fill(0, 4, $operation)));
        $this->assertSame(1, Models\Result::query()->count());
        $this->assertSame(1, DB::table('activity_log')->whereNotNull('properties->result_value')->count());
        $this->assertSame('0', Models\Result::query()->firstOrFail()->inserted_value);
        $this->assertNotNull($roots->first()->fresh()->init_date);
        $this->assertSame('Em análise', $product->fresh()->sample_status);
        $this->assertSame('EN_PROGRESO', $entry->fresh()->status);
    }

    public function test_native_sibling_roots_share_the_parent_lock_without_losing_stage_evidence(): void
    {
        [$operator, $lab, $entry, $product, $roots] = $this->fixture();
        $operations = $roots->map(fn (Models\Analysis $root): array => $this->operation($operator, $lab, $root))->all();
        $this->assertSame([200, 200], $this->compete($lab, $operations));
        $this->assertSame(2, Models\Result::query()->count());
        $this->assertSame(2, DB::table('activity_log')->whereNotNull('properties->result_value')->count());
        foreach ($roots as $root) {
            $this->assertNotNull($root->fresh()->init_date);
            $this->assertNull($root->fresh()->end_date);
        }
        $this->assertSame($product->processed, $product->fresh()->processed);
        $this->assertSame('EN_PROGRESO', $entry->fresh()->status);
    }

    #[DataProvider('reviewStages')]
    public function test_four_native_signed_replays_keep_one_stage_signature_and_audit(string $stage): void
    {
        [$operator, $lab, $entry, $product, $roots] = $this->fixture();
        $root = $roots->first();
        $this->prepareStage($operator, $lab, $root, $stage);
        $operation = $this->operation($operator, $lab, $root, $stage);
        $auditCount = $this->stageAuditCount();
        $mediaCount = DB::table('media')->count();
        $this->assertSame([200, 200, 200, 200], $this->compete($lab, array_fill(0, 4, $operation)));
        $result = Models\Result::query()->where('sample_id', $root->sample_id)->firstOrFail();
        $prefix = $stage === 'verify' ? 'verified' : 'approved';
        $this->assertSame('0', $result->{$prefix.'_value'});
        $this->assertNotNull($result->{$prefix.'_date'});
        $this->assertSame($auditCount + 1, $this->stageAuditCount());
        $this->assertSame($mediaCount + 1, DB::table('media')->count());
        $this->assertSame(1, $result->getMedia($stage === 'verify' ? 'verification_signature' : 'approval_signature')->count());
        $this->assertSignatureFiles();
        if ($stage === 'approve') {
            $this->assertNotNull($root->fresh()->end_date);
            $this->assertNull($roots->last()->fresh()->end_date);
        }
        $this->assertSame('Em análise', $product->fresh()->sample_status);
        $this->assertSame('EN_PROGRESO', $entry->fresh()->status);
        $snapshot = $this->snapshot();
        $this->assertSame([200, 200], $this->compete($lab, [$operation, $operation]));
        $this->assertSame($snapshot, $this->snapshot());
    }

    #[DataProvider('reviewStages')]
    public function test_native_signed_siblings_preserve_both_results_and_complete_only_after_approval(string $stage): void
    {
        [$operator, $lab, $entry, $product, $roots] = $this->fixture();
        foreach ($roots as $root) {
            $this->prepareStage($operator, $lab, $root, $stage);
        }
        $operations = $roots->map(fn (Models\Analysis $root): array => $this->operation($operator, $lab, $root, $stage))->all();
        $auditCount = $this->stageAuditCount();
        $mediaCount = DB::table('media')->count();
        $this->assertSame([200, 200], $this->compete($lab, $operations));
        $this->assertSame($auditCount + 2, $this->stageAuditCount());
        $this->assertSame($mediaCount + 2, DB::table('media')->count());
        $this->assertSignatureFiles();
        foreach ($roots as $root) {
            $result = Models\Result::query()->where('sample_id', $root->sample_id)->firstOrFail();
            $this->assertSame('0', $result->{$stage === 'verify' ? 'verified_value' : 'approved_value'});
            $this->assertSame($stage === 'approve', filled($root->fresh()->end_date));
        }
        $this->assertSame($stage === 'approve' ? 'Concluída' : 'Em análise', $product->fresh()->sample_status);
        $this->assertSame($stage === 'approve' ? 'COMPLETADO' : 'EN_PROGRESO', $entry->fresh()->status);
        $this->assertSame($stage === 'approve', filled($entry->fresh()->analysis_end_date));
    }

    #[DataProvider('allStages')]
    public function test_native_counter_stage_replay_preserves_source_and_completes_once(string $stage): void
    {
        [$operator, $lab, $entry, $product, $roots] = $this->fixture();
        $original = $roots->first();
        $this->runOperation($this->operation($operator, $lab, $original));
        $source = Models\Result::query()->where('sample_id', $original->sample_id)->firstOrFail();
        $counter = app(RequestLaboratoryCounterAnalysis::class)->execute($lab->id, $source->id, $operator->id);
        $this->prepareStage($operator, $lab, $counter, $stage);
        $sourceBefore = $source->fresh()->getAttributes();
        $parentsBefore = [$original->fresh()->getAttributes(), $product->fresh()->getAttributes(), $entry->fresh()->getAttributes()];
        $auditCount = $this->stageAuditCount();
        $mediaCount = DB::table('media')->count();
        $this->assertSame([200, 200, 200, 200], $this->compete($lab, array_fill(0, 4, $this->operation($operator, $lab, $counter, $stage))));
        $this->assertSame($auditCount + 1, $this->stageAuditCount());
        $this->assertSame(1, Models\Result::query()->where('sample_id', $counter->sample_id)->count());
        $result = Models\Result::query()->where('sample_id', $counter->sample_id)->firstOrFail();
        $prefix = match ($stage) {
            'analyze' => 'inserted', 'verify' => 'verified', 'approve' => 'approved',
        };
        $this->assertSame('0', $result->{$prefix.'_value'});
        $this->assertNotNull($result->{$prefix.'_date'});
        $this->assertSame($mediaCount + ($stage === 'analyze' ? 0 : 1), DB::table('media')->count());
        if ($stage !== 'analyze') {
            $this->assertSame(1, $result->getMedia($stage === 'verify' ? 'verification_signature' : 'approval_signature')->count());
        }
        $this->assertSame($parentsBefore, [$original->fresh()->getAttributes(), $product->fresh()->getAttributes(), $entry->fresh()->getAttributes()]);
        $sourceAfter = $source->fresh()->getAttributes();
        if ($stage === 'approve') {
            $this->assertFalse($source->fresh()->requested_counter_analysis);
            $this->assertNotNull($counter->fresh()->end_date);
            unset($sourceBefore['requested_counter_analysis'], $sourceBefore['updated_at'], $sourceAfter['requested_counter_analysis'], $sourceAfter['updated_at']);
        } else {
            $this->assertTrue($source->fresh()->requested_counter_analysis);
            $this->assertNull($counter->fresh()->end_date);
        }
        $this->assertSame($sourceBefore, $sourceAfter);
        $this->assertSignatureFiles();
    }

    #[DataProvider('signedRevocations')]
    public function test_waiting_signed_stage_preserves_existing_scientific_and_file_evidence(string $stage, string $revocation): void
    {
        [$operator, $lab, , , $roots] = $this->fixture();
        $root = $roots->first();
        $this->prepareStage($operator, $lab, $root, $stage);
        $qualification = Models\PersonnelQualification::query()->create(['user_id' => $operator->id, 'lab_id' => $lab->id,
            'department_id' => $root->department_id, 'capability' => '*', 'is_active' => true, 'authorized_until' => now()->addYear()]);
        $snapshot = $this->snapshot();
        $this->assertSame([403], $this->compete($lab, [$this->operation($operator, $lab, $root, $stage)], function () use ($revocation, $operator, $lab, $qualification): void {
            if ($revocation === 'membership') {
                DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $operator->id)->delete();
            } elseif ($revocation === 'permission') {
                $operator->syncPermissions([]);
            } elseif ($revocation === 'qualification') {
                $qualification->update(['authorized_until' => now()->subDay()]);
            } elseif ($revocation === 'laboratory') {
                $lab->delete();
            } else {
                $operator->forceFill($revocation === 'active' ? ['is_active' => false] : ['email_verified_at' => null])->save();
            }
        }));
        $this->assertSame($snapshot, $this->snapshot());
        $this->assertSignatureFiles();
    }

    public static function reviewStages(): array
    {
        return ['verification' => ['verify'], 'approval' => ['approve']];
    }

    public static function allStages(): array
    {
        return ['insertion' => ['analyze'], ...self::reviewStages()];
    }

    public static function signedRevocations(): array
    {
        $cases = [];
        foreach (['verify', 'approve'] as $stage) {
            foreach (['membership', 'permission', 'active', 'verification', 'laboratory', 'qualification'] as $revocation) {
                $cases[$stage.'-'.$revocation] = [$stage, $revocation];
            }
        }

        return $cases;
    }

    public function test_competing_approval_intents_cannot_replace_the_winning_approved_evidence(): void
    {
        [$operator, $lab, , , $roots] = $this->fixture();
        $root = $roots->first();
        $this->prepareStage($operator, $lab, $root, 'approve');
        $first = $this->operation($operator, $lab, $root, 'approve');
        $second = $first;
        $second['rows'][0]['approved_value'] = '1';
        $second['signature'] = self::ALTERNATE_SIGNATURE;
        $auditCount = $this->stageAuditCount();
        $mediaCount = DB::table('media')->count();
        $statuses = $this->compete($lab, [$first, $second]);
        sort($statuses);
        $this->assertSame([200, 422], $statuses);
        $this->assertSame($auditCount + 1, $this->stageAuditCount());
        $result = Models\Result::query()->where('sample_id', $root->sample_id)->firstOrFail();
        $this->assertContains($result->approved_value, ['0', '1']);
        $this->assertNotNull($root->fresh()->end_date);
        $winner = $result->approved_value === '0' ? $first : $second;
        $loser = $result->approved_value === '0' ? $second : $first;
        $this->assertSame($mediaCount + 1, DB::table('media')->count());
        $this->assertSame(1, $result->getMedia('approval_signature')->count());
        $this->assertSame(hash('sha256', base64_decode(explode(',', $winner['signature'])[1])), $result->getFirstMedia('approval_signature')->getCustomProperty('signature_sha256'));
        $snapshot = $this->snapshot();
        $this->assertSame([200, 422], $this->compete($lab, [$winner, $loser]));
        $this->assertSame($snapshot, $this->snapshot());
        $this->assertSignatureFiles([self::SIGNATURE, self::ALTERNATE_SIGNATURE]);
    }

    #[DataProvider('signedCommitStages')]
    public function test_signed_stage_has_real_outer_commit_visibility_and_file_rollback(string $stage, bool $counterWorkflow): void
    {
        [$operator, $lab, , , $roots] = $this->fixture();
        $root = $roots->first();
        if ($counterWorkflow) {
            $this->runOperation($this->operation($operator, $lab, $root));
            $source = Models\Result::query()->where('sample_id', $root->sample_id)->firstOrFail();
            $root = app(RequestLaboratoryCounterAnalysis::class)->execute($lab->id, $source->id, $operator->id);
        }
        $this->prepareStage($operator, $lab, $root, $stage);
        $operation = $this->operation($operator, $lab, $root, $stage);
        $before = $this->snapshot();
        config(['database.connections.result_signed_observer' => config('database.connections.pgsql')]);
        $observer = DB::connection('result_signed_observer');
        $databaseBefore = $this->databaseSnapshot($observer);
        try {
            foreach ([false, true] as $commit) {
                DB::beginTransaction();
                $this->runOperation($operation);
                $this->assertSame($databaseBefore, $this->databaseSnapshot($observer));
                $this->assertCount(count($before['files']) + 1, Storage::disk('public')->allFiles());
                if ($commit) {
                    DB::commit();
                } else {
                    DB::rollBack();
                    $this->assertSame($before, $this->snapshot());
                }
            }
            $this->assertSame($this->databaseSnapshot(DB::connection()), $this->databaseSnapshot($observer));
            $this->assertNotSame($databaseBefore, $this->databaseSnapshot($observer));
            $result = Models\Result::query()->where('sample_id', $root->sample_id)->firstOrFail();
            $this->assertSame('0', $result->{$stage === 'verify' ? 'verified_value' : 'approved_value'});
            $this->assertSignatureFiles();
            if ($counterWorkflow) {
                $this->assertSame($stage !== 'approve', $source->fresh()->requested_counter_analysis);
            }
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('result_signed_observer');
        }
    }

    public static function signedCommitStages(): array
    {
        return ['analysis-verify' => ['verify', false], 'analysis-approve' => ['approve', false],
            'counter-verify' => ['verify', true], 'counter-approve' => ['approve', true]];
    }

    #[DataProvider('revocations')]
    public function test_waiting_stage_rechecks_fresh_authority_before_any_scientific_write(string $revocation): void
    {
        [$operator, $lab, $entry, $product, $roots] = $this->fixture();
        $root = $roots->first();
        $before = [$root->getAttributes(), $product->getAttributes(), $entry->getAttributes()];
        $this->assertSame([403], $this->compete($lab, [$this->operation($operator, $lab, $root)], function () use ($revocation, $operator, $lab): void {
            if ($revocation === 'membership') {
                DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $operator->id)->delete();
            } elseif ($revocation === 'permission') {
                $operator->syncPermissions([]);
            } elseif ($revocation === 'laboratory') {
                $lab->delete();
            } else {
                $operator->forceFill($revocation === 'active' ? ['is_active' => false] : ['email_verified_at' => null])->save();
            }
        }));
        $this->assertSame($before, [$root->fresh()->getAttributes(), $product->fresh()->getAttributes(), $entry->fresh()->getAttributes()]);
        $this->assertSame(0, Models\Result::query()->count());
        $this->assertSame(0, DB::table('activity_log')->whereNotNull('properties->result_value')->count());
    }

    public static function revocations(): array
    {
        return array_combine(['membership', 'permission', 'active', 'verification', 'laboratory'], array_map(fn (string $value): array => [$value], ['membership', 'permission', 'active', 'verification', 'laboratory']));
    }

    public function test_scientific_stage_and_parent_changes_follow_the_real_outer_commit(): void
    {
        [$operator, $lab, $entry, $product, $roots] = $this->fixture();
        $root = $roots->first();
        $operation = $this->operation($operator, $lab, $root);
        $before = [$root->getAttributes(), $product->getAttributes(), $entry->getAttributes()];
        config(['database.connections.result_stage_observer' => config('database.connections.pgsql')]);
        $observer = DB::connection('result_stage_observer');
        try {
            foreach ([false, true] as $commit) {
                DB::beginTransaction();
                app(ProcessLaboratoryResults::class)->execute($lab->id, $root->id, $operator->id, 'analysis', 'analyze', $operation['rows']);
                $this->assertSame(0, $observer->table('results')->count());
                $this->assertNull($observer->table('analysis')->where('id', $root->id)->value('init_date'));
                $this->assertSame($entry->status, $observer->table('sample_entries')->where('id', $entry->id)->value('status'));
                $this->assertSame(0, $observer->table('activity_log')->whereNotNull('properties->result_value')->count());
                if ($commit) {
                    DB::commit();
                } else {
                    DB::rollBack();
                    $this->assertSame($before, [$root->fresh()->getAttributes(), $product->fresh()->getAttributes(), $entry->fresh()->getAttributes()]);
                }
            }
            $this->assertSame(1, $observer->table('results')->count());
            $this->assertNotNull($observer->table('analysis')->where('id', $root->id)->value('init_date'));
            $this->assertSame('EN_PROGRESO', $observer->table('sample_entries')->where('id', $entry->id)->value('status'));
            $this->assertSame('Em análise', $observer->table('collection_product')->where('id', $product->id)->value('sample_status'));
            $this->assertSame(1, $observer->table('activity_log')->whereNotNull('properties->result_value')->count());
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('result_stage_observer');
        }
    }

    private function fixture(): array
    {
        $operator = Models\User::factory()->create(['is_active' => true]);
        foreach (['insert_results', 'verify_results', 'approve_results', 'add_counter_analysis'] as $permission) {
            $operator->givePermissionTo(Models\Permission::findOrCreate($permission, 'web'));
        }
        $lab = Models\VAPLab::factory()->create();
        DB::table('lab_user')->insert(['user_id' => $operator->id, 'lab_id' => $lab->id]);
        $department = Models\Department::factory()->create();
        $customer = Models\Customer::query()->create(['name' => 'Native result customer']);
        $warehouse = Models\Warehouse::query()->create(['name' => 'Native result site', 'customer_id' => $customer->id]);
        $category = Models\AnalysisCategory::query()->create(['name' => 'Native result category', 'code' => fake()->uuid(), 'department_id' => $department->id]);
        $matrix = Models\Matrix::query()->create(['code' => fake()->uuid()]);
        $profileIds = [];
        for ($index = 0; $index < 2; $index++) {
            $profile = Models\Profile::query()->create(['name' => 'Native profile '.$index, 'code' => fake()->uuid(), 'category_id' => $category->id]);
            $parameter = Models\Parameter::query()->create(['name' => 'Native parameter '.$index, 'code' => fake()->uuid(), 'active' => true]);
            $profile->parameters()->attach($parameter);
            $matrix->profiles()->attach($profile);
            $profileIds[] = $profile->id;
        }
        $catalogProduct = Models\Product::query()->create(['name' => 'Native result product', 'matrix_id' => $matrix->id]);
        $payload = app(PrepareSampleEntryPayload::class)->execute([
            'code' => null, 'lab_id' => $lab->id, 'department_id' => $department->id, 'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id,
            'client_submitted_info' => ['collection_type' => 'direct', 'product_id' => $catalogProduct->id, 'requested_profile_ids' => $profileIds],
        ], null);
        $entry = Models\VAPSampleEntry::factory()->create($payload);
        $product = app(SampleEntryCollectionFlowService::class)->sync($entry);
        $roots = Models\Analysis::query()->where('cl_id', $product->code->id)->orderBy('id')->get();
        $this->assertCount(2, $roots);
        Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);
        Notification::fake();

        return [$operator, $lab, $entry->fresh(), $product->fresh(), $roots];
    }

    private function operation(Models\User $operator, Models\VAPLab $lab, Models\Analysis|Models\CounterAnalysis $root, string $stage = 'analyze'): array
    {
        $prefix = match ($stage) {
            'analyze' => 'inserted', 'verify' => 'verified', 'approve' => 'approved',
        };
        $row = ['parameter_id' => $root->profile->parameters->firstOrFail()->id, $prefix.'_value' => '0'];
        if ($stage !== 'analyze') {
            $row['result_id'] = Models\Result::query()->where('sample_id', $root->sample_id)->firstOrFail()->id;
        }

        return ['actor' => $operator->id, 'lab' => $lab->id, 'root' => $root->id, 'stage' => $stage,
            'workflow' => $root instanceof Models\CounterAnalysis ? 'counter' : 'analysis',
            'signature' => $stage === 'analyze' ? null : self::SIGNATURE, 'rows' => [$row]];
    }

    private function prepareStage(Models\User $operator, Models\VAPLab $lab, Models\Analysis|Models\CounterAnalysis $root, string $stage): void
    {
        foreach (['analyze', 'verify', 'approve'] as $previous) {
            if ($previous === $stage) {
                break;
            }
            $this->runOperation($this->operation($operator, $lab, $root, $previous));
        }
    }

    /** @param array<string, mixed> $operation */
    private function runOperation(array $operation): void
    {
        app(ProcessLaboratoryResults::class)->execute($operation['lab'], $operation['root'], $operation['actor'],
            $operation['workflow'], $operation['stage'], $operation['rows'], $operation['signature']);
    }

    private function stageAuditCount(): int
    {
        return DB::table('activity_log')->whereNotNull('properties->result_value')->count();
    }

    /** @return array<string, mixed> */
    private function snapshot(): array
    {
        $snapshot = $this->databaseSnapshot(DB::connection());
        $snapshot['files'] = [];
        foreach (Storage::disk('public')->allFiles() as $path) {
            $snapshot['files'][$path] = Storage::disk('public')->get($path);
        }
        ksort($snapshot['files']);

        return $snapshot;
    }

    /** @return array<string, mixed> */
    private function databaseSnapshot(ConnectionInterface $connection): array
    {
        $snapshot = [];
        foreach (['analysis', 'counter_analysis', 'collection_product', 'sample_entries', 'results', 'media'] as $table) {
            $snapshot[$table] = $connection->table($table)->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
        }
        $snapshot['audit'] = $connection->table('activity_log')->whereNotNull('properties->result_value')->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();

        return $snapshot;
    }

    /** @param list<string> $allowedSignatures */
    private function assertSignatureFiles(array $allowedSignatures = [self::SIGNATURE]): void
    {
        $media = Media::query()->get();
        $this->assertCount($media->count(), Storage::disk('public')->allFiles());
        foreach ($media as $signature) {
            $this->assertContains($signature->collection_name, ['verification_signature', 'approval_signature']);
            $bytes = Storage::disk('public')->get($signature->getPathRelativeToRoot());
            $this->assertContains($bytes, array_map(fn (string $value): string => base64_decode(explode(',', $value)[1]), $allowedSignatures));
            $this->assertSame(hash('sha256', $bytes), $signature->getCustomProperty('signature_sha256'));
        }
    }

    /** @param list<array<string,mixed>> $operations @return list<int> */
    private function compete(Models\VAPLab $lab, array $operations, ?callable $beforeRelease = null): array
    {
        $connection = DB::connection();
        $barrier = sys_get_temp_dir().'/result-stage-race-'.bin2hex(random_bytes(8));
        mkdir($barrier, 0700);
        $processes = [];
        $worker = <<<'PHP'
        require getcwd().'/vendor/autoload.php';
        $app = require getcwd().'/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $connection = Illuminate\Support\Facades\DB::connection();
        if (! $app->environment('testing') || $connection->getDatabaseName() !== 'lims_unleashed_test' || $connection->getConfig('search_path') !== $argv[1]) {
            throw new RuntimeException('Result concurrency requires its dedicated test schema.');
        }
        if ($argv[5] !== sys_get_temp_dir().'/result-stage-files-'.$argv[1] || ! is_dir($argv[5])) {
            throw new RuntimeException('Result concurrency requires its dedicated media directory.');
        }
        config(['filesystems.disks.public.root' => $argv[5], 'media-library.disk_name' => 'public']);
        Illuminate\Support\Facades\Storage::forgetDisk('public');
        Illuminate\Support\Facades\Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);
        Illuminate\Support\Facades\Notification::fake();
        $pid = $connection->selectOne('select pg_backend_pid() as pid')->pid;
        $connection->beforeExecuting(function (string $query) use ($argv, $pid): void {
            if (str_contains($query, 'from "labs"') && str_contains($query, 'for update')) {
                file_put_contents($argv[3].'/boundary-'.$argv[4], (string) $pid);
            }
        });
        $operation = json_decode($argv[2], true, flags: JSON_THROW_ON_ERROR);
        try {
            $app->make(App\Actions\ProcessLaboratoryResults::class)->execute($operation['lab'], $operation['root'], $operation['actor'], $operation['workflow'], $operation['stage'], $operation['rows'], $operation['signature']);
            $status = 200;
        } catch (Illuminate\Auth\Access\AuthorizationException) {
            $status = 403;
        } catch (Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $status = $exception->getStatusCode();
        } catch (Illuminate\Validation\ValidationException) {
            $status = 422;
        }
        echo json_encode(['status' => $status]);
        PHP;
        $environment = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DATABASE_URL' => '', 'DB_SCHEMA' => $this->schema,
            'DB_HOST' => $connection->getConfig('host'), 'DB_PORT' => (string) $connection->getConfig('port'), 'DB_DATABASE' => $connection->getDatabaseName(),
            'DB_USERNAME' => $connection->getConfig('username'), 'DB_PASSWORD' => $connection->getConfig('password') ?? '',
            'SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array', 'CACHE_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'database', 'MAIL_MAILER' => 'array'];
        try {
            DB::beginTransaction();
            DB::table('labs')->where('id', $lab->id)->lockForUpdate()->first();
            foreach ($operations as $index => $operation) {
                $process = new Process([PHP_BINARY, '-r', $worker, $this->schema, json_encode($operation, JSON_THROW_ON_ERROR), $barrier, (string) $index, $this->mediaRoot], base_path(), $environment, timeout: 30);
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
            $workerIds = array_map(fn (string $path): int => (int) file_get_contents($path), glob($barrier.'/boundary-*') ?: []);
            $deadline = microtime(true) + 15;
            do {
                DB::select('select pg_stat_clear_snapshot()');
                $waiting = DB::table('pg_stat_activity')->whereIn('pid', $workerIds)->where('wait_event_type', 'Lock')->pluck('pid')->all();
                if (count($waiting) === count($operations)) {
                    break;
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            $this->assertCount(count($operations), $waiting, 'Every worker must actually wait on the held PostgreSQL access lock.');
            $beforeRelease?->__invoke();
            DB::commit();
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
                $results[] = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR)['status'];
            }

            return $results;
        } finally {
            while (DB::transactionLevel() > 0) {
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
