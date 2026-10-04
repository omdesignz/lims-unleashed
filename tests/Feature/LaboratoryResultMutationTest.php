<?php

namespace Tests\Feature;

use App\Actions\PrepareSampleEntryPayload;
use App\Actions\ProcessLaboratoryResults;
use App\Actions\RequestLaboratoryCounterAnalysis;
use App\Jobs;
use App\Models;
use App\Services\LaboratoryResultSignatures;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LaboratoryResultMutationTest extends TestCase
{
    use DatabaseTransactions;

    private const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9sX6lz4AAAAASUVORK5CYII=';

    private Models\VAPLab $lab;

    private Models\VAPLab $peerLab;

    private Models\User $operator;

    /** The colleague who inserted and verified seeded results, so the operator can review them. */
    private Models\User $colleague;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->lab = Models\VAPLab::factory()->create();
        $this->peerLab = Models\VAPLab::factory()->create();
        $this->operator = Models\User::factory()->create(['is_active' => true]);
        $this->colleague = Models\User::factory()->create(['is_active' => true]);
        foreach (['insert_results', 'verify_results', 'approve_results', 'view_results', 'add_counter_analysis'] as $permission) {
            $this->operator->givePermissionTo(Models\Permission::findOrCreate($permission, 'web'));
        }
        DB::table('lab_user')->insert(['user_id' => $this->operator->id, 'lab_id' => $this->lab->id]);
        Storage::fake('public');
        Storage::fake('local');
        config(['media-library.disk_name' => 'public']);
        Notification::fake();
        Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);
    }

    #[DataProvider('jobs')]
    public function test_jobs_keep_the_issued_parameter_set_after_the_catalogue_grows(string $class, string $stage, bool $counter, bool $individual): void
    {
        [$root, $product, $entry] = $this->fixture($this->lab, $counter);
        $rows = $this->rows($root, $stage);
        $issued = collect($entry->client_submitted_info['required_parameters'])->keyBy('id');
        $extra = Models\Parameter::query()->create(['name' => 'Added after issuance', 'code' => fake()->unique()->bothify('NEW-########')]);
        $root->profile->parameters()->attach($extra);
        $first = $root->profile->parameters->first();
        $first->update(['name' => 'Renamed after issuance', 'code' => fake()->unique()->bothify('EDIT-########')]);
        $this->job($class, $root, $individual ? [$rows[0]] : $rows, $individual)->handle(app(ProcessLaboratoryResults::class));

        $this->assertFalse(Models\Result::query()->where('sample_id', $root->sample_id)->where('parameter_id', $extra->id)->exists());
        $result = Models\Result::query()->where('sample_id', $root->sample_id)->where('parameter_id', $rows[0]['parameter_id'])->firstOrFail();
        if ($stage === 'analyze') {
            $this->assertSame($issued[$result->parameter_id]['name'], $result->parameter_label);
        }
        if ($stage === 'approve' && ! $individual) {
            $this->assertNotNull($root->fresh()->end_date);
        }
        $this->assertSame($issued->all(), collect($entry->fresh()->client_submitted_info['required_parameters'])->keyBy('id')->all());
    }

    public function test_insert_uses_issued_parameter_configuration_after_catalogue_detachment(): void
    {
        [$root, , $entry] = $this->fixture($this->lab);
        $rows = $this->rows($root, 'analyze');
        $issued = collect($entry->client_submitted_info['required_parameters'])->keyBy('id');
        $first = $root->profile->parameters->first();
        $root->profile->parameters()->detach($first->id);
        $first->update(['name' => 'Catalogue changed after issuance']);
        $rows[0]['min_ref_value'] = '999';
        $rows[0]['max_ref_value'] = '1000';
        $rows[0]['ref_val_origin'] = 'forged';

        $this->job(Jobs\InsertAnalysisResults::class, $root, $rows)->handle(app(ProcessLaboratoryResults::class));

        $result = Models\Result::query()->where('sample_id', $root->sample_id)->where('parameter_id', $first->id)->firstOrFail();
        $this->assertSame($issued[$first->id]['name'], $result->parameter_label);
        $this->assertSame(data_get($issued[$first->id], 'profile_definitions.0.category_id'), $result->type_id);
        $this->assertSame(data_get($issued[$first->id], 'profile_definitions.0.unit_id'), $result->unit_id);
        $this->assertSame(data_get($issued[$first->id], 'profile_definitions.0.protocol_id'), $result->protocol_id);
        $this->assertSame(data_get($issued[$first->id], 'profile_definitions.0.standard_id'), $result->standard_id);
        $this->assertSame(data_get($issued[$first->id], 'profile_definitions.0.nwp_id'), $result->nwp_id);
        $this->assertSame(data_get($issued[$first->id], 'profile_definitions.0.min_ref_value'), $result->min_ref_value);
        $this->assertSame(data_get($issued[$first->id], 'profile_definitions.0.max_ref_value'), $result->max_ref_value);
        $this->assertSame(data_get($issued[$first->id], 'profile_definitions.0.ref_val_origin'), $result->ref_val_origin);
    }

    public function test_insert_copies_the_issued_reporting_definition_and_keeps_the_analysts_method_deviation(): void
    {
        [$root, , $entry] = $this->fixture($this->lab, definition: ['accredited' => true, 'subcontractor' => 'Laboratório externo', 'uncertainty_coverage_factor' => 2]);
        $rows = $this->rows($root, 'analyze');
        $rows[0]['method_deviation'] = 'Toma analítica de 5 g em vez de 10 g.';
        // The report definition comes from the issued scope, not from the submitted row.
        $rows[0]['accredited'] = false;
        $rows[0]['subcontractor'] = null;
        $rows[0]['uncertainty_coverage_factor'] = '9';
        $first = $root->profile->parameters->first();
        $root->profile->parameters()->updateExistingPivot($first->id, ['accredited' => false, 'subcontractor' => null, 'uncertainty_coverage_factor' => null]);

        $this->job(Jobs\InsertAnalysisResults::class, $root, $rows)->handle(app(ProcessLaboratoryResults::class));

        $issued = collect($entry->client_submitted_info['required_parameters'])->keyBy('id');
        $this->assertTrue(data_get($issued[$first->id], 'profile_definitions.0.accredited'));
        $result = Models\Result::query()->where('sample_id', $root->sample_id)->where('parameter_id', $first->id)->firstOrFail();
        $this->assertTrue($result->accredited);
        $this->assertSame('Laboratório externo', $result->subcontractor);
        $this->assertSame('2.00', $result->uncertainty_coverage_factor);
        $this->assertSame('Toma analítica de 5 g em vez de 10 g.', $result->method_deviation);
        $this->assertNull(Models\Result::query()->where('sample_id', $root->sample_id)->where('parameter_id', '!=', $first->id)->value('method_deviation'));
    }

    public function test_missing_issued_scope_fails_before_writing_result_evidence(): void
    {
        [$root, , $entry] = $this->fixture($this->lab);
        $rows = $this->rows($root, 'analyze');
        $info = $entry->client_submitted_info;
        unset($info['required_parameters']);
        DB::table('sample_entries')->where('id', $entry->id)->update(['client_submitted_info' => json_encode($info, JSON_THROW_ON_ERROR)]);
        $before = Models\Result::query()->where('sample_id', $root->sample_id)->count();

        $this->reject(fn () => $this->job(Jobs\InsertAnalysisResults::class, $root, $rows)->handle(app(ProcessLaboratoryResults::class)));
        $this->assertSame($before, Models\Result::query()->where('sample_id', $root->sample_id)->count());
    }

    public function test_incomplete_issued_profile_definition_fails_before_result_insertion(): void
    {
        [$root, , $entry] = $this->fixture($this->lab);
        $rows = $this->rows($root, 'analyze');
        $info = $entry->client_submitted_info;
        $info['required_parameters'][0]['profile_definitions'] = [];
        DB::table('sample_entries')->where('id', $entry->id)->update(['client_submitted_info' => json_encode($info, JSON_THROW_ON_ERROR)]);

        $this->reject(fn () => $this->job(Jobs\InsertAnalysisResults::class, $root, $rows)->handle(app(ProcessLaboratoryResults::class)));
        $this->assertSame(0, Models\Result::query()->where('sample_id', $root->sample_id)->count());
    }

    public function test_result_entry_forms_keep_the_issued_scope_after_catalogue_changes(): void
    {
        foreach ([false, true] as $counter) {
            [$root, , $entry] = $this->fixture($this->lab, $counter);
            $issued = collect($entry->client_submitted_info['required_parameters'])->keyBy('id');
            $first = $root->profile->parameters->firstOrFail();
            $root->profile->parameters()->detach($first->id);
            $first->update(['name' => 'Changed after issuance', 'code' => 'CHANGED-'.$first->id]);
            $extra = Models\Parameter::query()->create(['name' => 'Added after issuance', 'code' => 'NEW-'.fake()->unique()->bothify('######')]);
            $root->profile->parameters()->attach($extra);

            $route = $counter ? 'results.getCounterAnalysisDefaultResultsData' : 'results.getDefaultResultsData';
            $payload = $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id])
                ->getJson(route($route, ['sample_id' => $root->sample_id, 'action' => 'analyze']))
                ->assertOk()->assertJsonCount(2)->json();

            $this->assertEqualsCanonicalizing($issued->keys()->all(), collect($payload)->pluck('parameter_id.value')->all());
            $original = collect($payload)->firstWhere('parameter_id.value', $first->id);
            $this->assertSame($issued[$first->id]['name'], data_get($original, 'parameter_label'));
            $this->assertSame($issued[$first->id]['code'], data_get($original, 'parameter_id.code'));
            $this->assertSame(data_get($issued[$first->id], 'profile_definitions.0.unit_label'), data_get($original, 'unit_label'));
        }
    }

    public function test_result_submission_accepts_the_issued_scope_after_catalogue_changes(): void
    {
        [$root] = $this->fixture($this->lab);
        $first = $root->profile->parameters->firstOrFail();
        $root->profile->parameters()->detach($first->id);
        $added = Models\Parameter::query()->create(['name' => 'Late parameter', 'code' => 'LATE-'.fake()->unique()->bothify('######')]);
        $root->profile->parameters()->attach($added);
        $payload = $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id])
            ->getJson(route('results.getDefaultResultsData', ['sample_id' => $root->sample_id, 'action' => 'analyze']))
            ->assertOk()->json();
        foreach ($payload as &$row) {
            $row['inserted_value'] = '1.25';
        }
        unset($row);
        Bus::fake([Jobs\InsertAnalysisResults::class]);

        $this->post(route('results.store'), ['action' => 'analyze', 'sample_id' => $root->sample_id, 'results' => $payload])
            ->assertRedirect()->assertSessionHasNoErrors();
        Bus::assertDispatched(Jobs\InsertAnalysisResults::class);
    }

    public function test_review_forms_keep_issued_parameter_metadata_after_catalogue_changes(): void
    {
        foreach ([false, true] as $counter) {
            [$root, , $entry] = $this->fixture($this->lab, $counter);
            $this->rows($root, 'verify');
            $first = $root->profile->parameters->firstOrFail();
            $original = collect($entry->client_submitted_info['required_parameters'])->firstWhere('id', $first->id);
            $first->update(['name' => 'Renamed during review', 'code' => 'REVIEW-'.$first->id]);

            $route = $counter ? 'results.getCounterAnalysisDefaultResultsData' : 'results.getDefaultResultsData';
            $payload = $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id])
                ->getJson(route($route, ['sample_id' => $root->sample_id, 'action' => 'verify']))
                ->assertOk()->json();

            $row = collect($payload)->firstWhere('parameter_id.value', $first->id);
            $this->assertNotNull($row);
            $this->assertSame($original['name'], data_get($row, 'parameter_id.name'));
            $this->assertSame($original['code'], data_get($row, 'parameter_id.code'));
        }
    }

    #[DataProvider('jobs')]
    public function test_jobs_stamp_actor_preserve_other_stages_and_retry_without_duplicate_evidence(string $class, string $stage, bool $counter, bool $individual): void
    {
        [$root, $product] = $this->fixture($this->lab, $counter);
        $rows = $this->rows($root, $stage);
        $job = $this->job($class, $root, $individual ? [$rows[0]] : $rows, $individual);
        $this->assertTrue($job->afterCommit);
        DB::table('lab_user')->insert(['user_id' => $this->operator->id, 'lab_id' => $this->peerLab->id]);
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->peerLab->id]);
        $job->handle(app(ProcessLaboratoryResults::class));
        $prefix = match ($stage) {
            'analyze' => 'inserted', 'verify' => 'verified', 'approve' => 'approved'
        };
        $result = Models\Result::query()->where('sample_id', $root->sample_id)->where('parameter_id', $rows[0]['parameter_id'])->firstOrFail();
        $this->assertSame($this->operator->id, $result->{$prefix.'_by_id'});
        $this->assertSame($this->operator->name, $result->{$prefix.'_by'});
        $this->assertNotSame('2000-01-01 00:00:00', $result->{$prefix.'_date'});
        $this->assertSame('scientific', data_get($result->extra_data, 'display_format'));
        if ($stage === 'analyze') {
            $this->assertNull($result->verified_by_id);
            $this->assertNull($result->approved_value);
            $this->assertSame($root->id, $result->resultable_id);
            $this->assertSame($product->id, $result->collection_id);
            $this->assertNotNull($root->fresh()->init_date);
        } else {
            $this->assertSame('Original technician', $result->inserted_by);
            $this->assertSame('1.25', $result->inserted_value);
            $this->assertSame('Original provenance', data_get($result->extra_data, 'provenance'));
            $this->assertNotNull($result->getFirstMedia($stage === 'verify' ? 'verification_signature' : 'approval_signature'));
            if ($stage === 'verify') {
                $this->assertNull($result->approved_value);
            } else {
                $this->assertSame('Original verifier', $result->verified_by);
                $this->assertSame('1.5', $result->verified_value);
                $this->assertSame('Approval observation', $result->approval_notes);
            }
        }
        $before = $result->fresh()->getAttributes();
        $counts = [Models\Result::query()->count(), Media::query()->count(), DB::table('activity_log')->count()];
        $this->travel(2)->hours();
        $job->handle(app(ProcessLaboratoryResults::class));
        $this->assertSame($before, $result->fresh()->getAttributes());
        $this->assertSame($counts, [Models\Result::query()->count(), Media::query()->count(), DB::table('activity_log')->count()]);
    }

    #[DataProvider('invalidJobs')]
    public function test_jobs_reject_stale_actor_or_source_without_writes(string $class, string $stage, bool $counter, bool $individual, string $change): void
    {
        [$root, $product, $entry] = $this->fixture($this->lab, $counter);
        [$foreign] = $this->fixture($this->peerLab);
        $rows = $this->rows($root, $stage);
        $job = $this->job($class, $root, $individual ? [$rows[0]] : $rows, $individual);
        $permission = match ($stage) {
            'analyze' => 'insert_results', 'verify' => 'verify_results', 'approve' => 'approve_results'
        };
        if ($change === 'ambiguous_owner') {
            DB::statement('SET CONSTRAINTS sample_entries_collection_product_unique DEFERRED');
        }

        match ($change) {
            'removed_member' => DB::table('lab_user')->where('user_id', $this->operator->id)->delete(),
            'inactive_actor' => $this->operator->update(['is_active' => false]),
            'unverified_actor' => $this->operator->update(['email_verified_at' => null]),
            'revoked_permission' => $this->operator->revokePermissionTo($permission),
            'foreign_root' => $job->analysis_id = $foreign->id,
            'deleted_entry' => $entry->delete(),
            'ambiguous_owner' => Models\VAPSampleEntry::factory()->create(['lab_id' => $this->peerLab->id,
                'collection_product_id' => $product->id, 'customer_id' => $entry->customer_id])->delete(),
        };
        $before = DB::table('results')->orderBy('id')->get()->toArray();
        $rootBefore = $root->fresh()->getAttributes();
        $productBefore = $product->fresh()->getAttributes();
        $this->reject(fn () => $job->handle(app(ProcessLaboratoryResults::class)));
        $this->assertEquals($before, DB::table('results')->orderBy('id')->get()->toArray());
        $this->assertSame($rootBefore, $root->fresh()->getAttributes());
        $this->assertSame($productBefore, $product->fresh()->getAttributes());
        $this->assertSame([], Storage::disk('public')->allFiles());
        Event::assertNothingDispatched();
        Notification::assertNothingSent();
    }

    #[DataProvider('jobs')]
    public function test_legacy_jobs_without_lab_context_fail_closed(string $class, string $stage, bool $counter, bool $individual): void
    {
        [$root] = $this->fixture($this->lab, $counter);
        $rows = $this->rows($root, $stage);
        $legacy = $this->job($class, $root, $individual ? [$rows[0]] : $rows, $individual);
        unset($legacy->lab_id);
        $job = unserialize(serialize($legacy));
        $this->assertSame(0, $job->lab_id);
        $this->reject(fn () => $job->handle(app(ProcessLaboratoryResults::class)));
    }

    #[DataProvider('jobs')]
    public function test_foreign_row_or_lineage_never_changes_a_local_batch(string $class, string $stage, bool $counter, bool $individual): void
    {
        [$root] = $this->fixture($this->lab, $counter);
        [$foreign] = $this->fixture($this->peerLab);
        $rows = $this->rows($root, $stage);
        $foreignRows = $this->rows($foreign, $stage);
        $index = $individual ? 0 : 1;
        if ($stage === 'analyze') {
            $rows[$index]['code_id'] = $foreign->cl_id;
        } else {
            $rows[$index]['result_id'] = $foreignRows[$index]['result_id'];
        }
        $before = DB::table('results')->orderBy('id')->get()->toArray();
        $this->reject(fn () => $this->job($class, $root, $individual ? [$rows[0]] : $rows, $individual)->handle(app(ProcessLaboratoryResults::class)));
        $this->assertEquals($before, DB::table('results')->orderBy('id')->get()->toArray());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failed_later_insert_rolls_back_results_activity_and_start_dates(): void
    {
        [$root, $product, $entry] = $this->fixture($this->lab);
        $rows = $this->rows($root, 'analyze');
        Models\Result::creating(function (Models\Result $result) use ($rows): void {
            if ((int) $result->parameter_id === $rows[1]['parameter_id']) {
                throw new RuntimeException('Second result failed');
            }
        });
        $activity = DB::table('activity_log')->count();
        $this->failsPersistence(fn () => $this->job(Jobs\InsertAnalysisResults::class, $root, $rows)->handle(app(ProcessLaboratoryResults::class)));
        $this->assertSame(0, Models\Result::query()->where('sample_id', $root->sample_id)->count());
        $this->assertSame($activity, DB::table('activity_log')->count());
        $this->assertNull($root->fresh()->init_date);
        $this->assertNull($product->fresh()->analysis_start_date);
        $this->assertNull($entry->fresh()->analysis_start_date);
    }

    public function test_failed_signature_replacement_preserves_old_file_and_cleans_staged_file(): void
    {
        [$root] = $this->fixture($this->lab);
        $rows = $this->rows($root, 'verify');
        $first = Models\Result::query()->findOrFail($rows[0]['result_id']);
        $old = $first->addMediaFromBase64(self::SIGNATURE)->usingFileName('old.png')->toMediaCollection('verification_signature');
        $bytes = Storage::disk($old->disk)->get($old->getPathRelativeToRoot());
        $files = Storage::disk('public')->allFiles();
        $before = DB::table('results')->orderBy('id')->get()->toArray();
        Models\Result::updating(function (Models\Result $result) use ($rows): void {
            if ($result->id === $rows[1]['result_id'] && $result->isDirty('verified_value')) {
                throw new RuntimeException('Second result failed');
            }
        });
        $this->failsPersistence(fn () => $this->job(Jobs\VerifyAnalysisResults::class, $root, $rows)->handle(app(ProcessLaboratoryResults::class)));
        $this->assertEquals($before, DB::table('results')->orderBy('id')->get()->toArray());
        $this->assertSame($files, Storage::disk('public')->allFiles());
        $this->assertSame($bytes, Storage::disk($old->disk)->get($old->getPathRelativeToRoot()));
        $this->assertSame($old->id, $first->fresh()->getFirstMedia('verification_signature')?->id);
    }

    public function test_outer_rollback_also_cleans_successfully_staged_signature_files(): void
    {
        [$root] = $this->fixture($this->lab);
        $rows = $this->rows($root, 'verify');
        $first = Models\Result::query()->findOrFail($rows[0]['result_id']);
        $old = $first->addMediaFromBase64(self::SIGNATURE)->usingFileName('old.png')->toMediaCollection('verification_signature');
        $files = Storage::disk('public')->allFiles();
        DB::beginTransaction();
        try {
            $this->job(Jobs\VerifyAnalysisResults::class, $root, $rows)->handle(app(ProcessLaboratoryResults::class));
            $this->assertGreaterThan(count($files), count(Storage::disk('public')->allFiles()));
        } finally {
            DB::rollBack();
        }
        $this->assertSame($files, Storage::disk('public')->allFiles());
        $this->assertSame($old->id, $first->fresh()->getFirstMedia('verification_signature')?->id);
        $this->assertNull($first->fresh()->verified_date);
    }

    public function test_worker_rechecks_expired_qualification_and_blocked_persisted_equipment(): void
    {
        [$root] = $this->fixture($this->lab);
        $rows = $this->rows($root, 'verify');
        $qualification = Models\PersonnelQualification::query()->create(['user_id' => $this->operator->id,
            'lab_id' => $this->lab->id,
            'capability' => 'verify_results', 'department_id' => $root->department_id, 'is_active' => true, 'authorized_until' => now()->addYear()]);
        $this->operator->load('personnelQualifications');
        $job = $this->job(Jobs\VerifyAnalysisResults::class, $root, $rows);
        $qualification->update(['authorized_until' => now()->subDay()]);
        $this->reject(fn () => $job->handle(app(ProcessLaboratoryResults::class)));
        $qualification->update(['authorized_until' => now()->addYear()]);
        $category = Models\ItemCategory::query()->withTrashed()->find(1)
            ?? Models\ItemCategory::query()->forceCreate(['id' => 1, 'name' => 'Equipment category']);
        $equipment = Models\InventoryItem::query()->create(['lab_id' => $this->lab->id, 'name' => 'Blocked instrument', 'category_id' => $category->id, 'code' => fake()->unique()->bothify('EQ-#######'),
            'metrological_uncertainty_value' => 0.1, 'metrological_uncertainty_unit' => 'mg',
            'metrological_traceability_reference' => 'TRACE', 'next_calibration_date' => now()->subDay()]);
        Models\Result::query()->findOrFail($rows[0]['result_id'])->update(['extra_data' => ['equipment' => ['equipment_id' => $equipment->id]]]);
        $this->reject(fn () => $job->handle(app(ProcessLaboratoryResults::class)));
        $this->assertFalse(Models\Result::query()->where('sample_id', $root->sample_id)->whereNotNull('verified_date')->exists());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_counter_completion_clears_only_requested_result_flag(): void
    {
        [$root] = $this->fixture($this->lab, true);
        $source = $root->requested_result;
        $unrelated = Models\Result::query()->where('sample_id', $source->sample_id)->whereKeyNot($source->id)->firstOrFail();
        $unrelated->update(['requested_counter_analysis' => true]);
        $this->job(Jobs\ApproveCounterAnalysisResults::class, $root, $this->rows($root, 'approve'))->handle(app(ProcessLaboratoryResults::class));
        $this->assertFalse($source->fresh()->requested_counter_analysis);
        $this->assertTrue($unrelated->fresh()->requested_counter_analysis);
        $this->assertTrue($root->fresh()->status);
    }

    public function test_individual_approval_waits_for_missing_parameters_and_other_analyses(): void
    {
        [$root, $product, $entry] = $this->fixture($this->lab);
        $rows = $this->rows($root, 'approve');
        $missing = Models\Result::query()->findOrFail($rows[1]['result_id']);
        $missing->delete();
        $this->job(Jobs\ApproveIndividualResult::class, $root, [$rows[0]], true)->handle(app(ProcessLaboratoryResults::class));
        $this->assertFalse($root->fresh()->status);
        $this->assertFalse($product->fresh()->status);
        $missing->restore();
        $otherSample = Models\Sample::query()->create(['cl_id' => $root->cl_id, 'sample_month' => now()->format('y/m')]);
        $other = Models\Analysis::query()->create(['cl_id' => $root->cl_id, 'sample_id' => $otherSample->id, 'profile_id' => $root->profile_id, 'product_id' => $root->product_id]);
        $this->job(Jobs\ApproveIndividualResult::class, $root, [$rows[1]], true)->handle(app(ProcessLaboratoryResults::class));
        $this->assertTrue($root->fresh()->status);
        $this->assertFalse($product->fresh()->status);
        $this->assertNull($entry->fresh()->analysis_end_date);
        $this->job(Jobs\ApproveAnalysisResults::class, $other, $this->rows($other, 'approve'))->handle(app(ProcessLaboratoryResults::class));
        $this->assertTrue($product->fresh()->status);
        $this->assertSame('Concluída', $product->fresh()->sample_status);
        $this->assertSame('COMPLETADO', $entry->fresh()->status);
    }

    public function test_http_normalization_preserves_equipment_notes_and_lab_context(): void
    {
        [$root] = $this->fixture($this->lab);
        $category = Models\ItemCategory::query()->withTrashed()->find(1)
            ?? Models\ItemCategory::query()->forceCreate(['id' => 1, 'name' => 'Equipment category']);
        $equipment = Models\InventoryItem::query()->create(['lab_id' => $this->lab->id, 'name' => 'Instrument', 'category_id' => $category->id, 'code' => fake()->unique()->bothify('EQ-#######')]);
        $rows = $this->rows($root, 'approve');
        foreach ($rows as &$row) {
            $row['equipment_id'] = ['value' => $equipment->id, 'label' => 'Instrument'];
        }
        unset($row);
        Bus::fake([Jobs\ApproveAnalysisResults::class]);
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id])
            ->postJson(route('results.store'), ['action' => 'approve', 'sample_id' => $root->sample_id, 'results' => $rows, 'signature' => self::SIGNATURE])
            ->assertRedirect()->assertSessionHasNoErrors();
        Bus::assertDispatched(Jobs\ApproveAnalysisResults::class, fn (Jobs\ApproveAnalysisResults $job): bool => $job->lab_id === $this->lab->id
            && $job->user_id === $this->operator->id && $job->results[0]['equipment_id'] === $equipment->id
            && $job->results[0]['approval_notes'] === 'Approval observation');
    }

    public function test_individual_http_submission_uses_scoped_validation_and_scalar_ids(): void
    {
        [$root] = $this->fixture($this->lab);
        [$foreign] = $this->fixture($this->peerLab);
        $rows = $this->rows($root, 'analyze');
        Bus::fake([Jobs\InsertIndividualResult::class]);
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id])
            ->postJson(route('results.store.individual'), array_replace($rows[0], ['sample_id' => $root->sample_id, 'action' => 'analyze']))
            ->assertOk()->assertJsonPath('success', true);
        Bus::assertDispatched(Jobs\InsertIndividualResult::class, fn (Jobs\InsertIndividualResult $job): bool => $job->lab_id === $this->lab->id && $job->results['parameter_id'] === $rows[0]['parameter_id']);
        $this->postJson(route('results.store.individual'), array_replace($rows[1], ['sample_id' => $foreign->sample_id, 'action' => 'analyze']))
            ->assertUnprocessable()->assertJsonValidationErrors('sample_id');
        $this->postJson(route('results.store.individual'), ['sample_id' => $root->sample_id, 'action' => 'analyze', 'results' => $rows])
            ->assertUnprocessable()->assertJsonValidationErrors('results');
        Bus::assertDispatchedTimes(Jobs\InsertIndividualResult::class, 1);
    }

    public function test_specification_evidence_is_derived_from_stored_limits_and_units(): void
    {
        [$root] = $this->fixture($this->lab);
        $rows = $this->rows($root, 'verify');
        $result = Models\Result::query()->findOrFail($rows[0]['result_id']);
        $result->update(['min_ref_value' => '0', 'max_ref_value' => '2']);
        $rows[0]['min_ref_value'] = '100';
        $rows[0]['max_ref_value'] = '200';
        $rows[0]['extra_data']['specification_check'] = ['within_limits' => true, 'unit_id' => 0];
        $this->job(Jobs\VerifyAnalysisResults::class, $root, $rows)->handle(app(ProcessLaboratoryResults::class));
        $stored = $result->fresh();
        $this->assertSame('0', $stored->min_ref_value);
        $this->assertSame('2', $stored->max_ref_value);
        $this->assertFalse(data_get($stored->extra_data, 'specification_check.within_limits'));
        $this->assertSame($stored->unit_id, data_get($stored->extra_data, 'specification_check.unit_id'));
    }

    public function test_normal_result_default_data_rejects_peer_samples_for_every_stage(): void
    {
        [$local] = $this->fixture($this->lab);
        [$foreign] = $this->fixture($this->peerLab);
        $this->rows($local, 'approve');
        $this->rows($foreign, 'approve');
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->peerLab->id]);
        foreach (['analyze', 'verify', 'approve'] as $stage) {
            $this->getJson(route('results.getDefaultResultsData', ['sample_id' => $foreign->sample_id, 'action' => $stage]))->assertNotFound();
            $this->getJson(route('results.getDefaultResultsData', ['sample_id' => $local->sample_id, 'action' => $stage]))->assertOk()->assertJsonCount(2);
        }
    }

    public function test_actor_rename_and_new_personal_signature_do_not_rewrite_an_identical_committed_job(): void
    {
        [$root] = $this->fixture($this->lab);
        $rows = $this->rows($root, 'verify');
        $this->operator->addMediaFromBase64(self::SIGNATURE)->usingFileName('personal.png')->toMediaCollection('signature');
        $job = new Jobs\VerifyAnalysisResults($rows, $root->id, $this->operator->id, $this->lab->id);
        $job->handle(app(ProcessLaboratoryResults::class));
        $result = Models\Result::query()->findOrFail($rows[0]['result_id']);
        $before = $result->getAttributes();
        $mediaId = $result->getFirstMedia('verification_signature')->id;
        $this->operator->update(['name' => 'Renamed operator']);
        $this->operator->clearMediaCollection('signature');
        $bytes = base64_decode(explode(',', self::SIGNATURE)[1]).'alternate signature';
        $this->operator->addMediaFromString($bytes)->usingFileName('new-personal.png')->toMediaCollection('signature');
        $job->handle(app(ProcessLaboratoryResults::class));
        $this->assertSame($before, $result->fresh()->getAttributes());
        $this->assertSame($mediaId, $result->fresh()->getFirstMedia('verification_signature')->id);
    }

    public function test_invalid_signature_content_is_rejected_before_writing_any_review_evidence(): void
    {
        [$root] = $this->fixture($this->lab);
        $rows = $this->rows($root, 'verify');
        $before = DB::table('results')->orderBy('id')->get()->toArray();
        foreach (['', 'data:image/png;base64,bm90LWFuLWltYWdl', 'data:image/svg+xml;base64,PHN2Zz48L3N2Zz4='] as $signature) {
            $job = new Jobs\VerifyAnalysisResults($rows, $root->id, $this->operator->id, $this->lab->id, $signature);
            $this->reject(fn () => $job->handle(app(ProcessLaboratoryResults::class)));
        }
        config(['media-library.max_file_size' => 1]);
        $this->reject(fn () => $this->job(Jobs\VerifyAnalysisResults::class, $root, $rows)->handle(app(ProcessLaboratoryResults::class)));
        $this->assertEquals($before, DB::table('results')->orderBy('id')->get()->toArray());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failure_logs_contain_identity_context_without_result_values_or_signatures(): void
    {
        [$root] = $this->fixture($this->lab);
        $job = $this->job(Jobs\InsertAnalysisResults::class, $root, $this->rows($root, 'analyze'));
        Log::shouldReceive('warning')->once()->with('Laboratory result mutation failed.',
            ['lab_id' => $this->lab->id, 'analysis_id' => $root->id, 'user_id' => $this->operator->id,
                'workflow' => 'analysis', 'stage' => 'analyze', 'exception' => RuntimeException::class]);
        $job->failed(new RuntimeException('Sensitive result content must not be logged here.'));
    }

    #[DataProvider('jobs')]
    public function test_empty_stage_values_are_rejected_atomically_but_zero_is_accepted(string $class, string $stage, bool $counter, bool $individual): void
    {
        [$root] = $this->fixture($this->lab, $counter);
        $rows = $this->rows($root, $stage);
        $prefix = match ($stage) {
            'analyze' => 'inserted', 'verify' => 'verified', 'approve' => 'approved',
        };
        $index = $individual ? 0 : 1;
        $before = DB::table('results')->orderBy('id')->get()->toArray();
        $activity = DB::table('activity_log')->count();
        foreach ([null, '', '   ', [], false] as $value) {
            $rows[$index][$prefix.'_value'] = $value;
            $this->reject(fn () => $this->job($class, $root, $rows, $individual)->handle(app(ProcessLaboratoryResults::class)));
            $this->assertEquals($before, DB::table('results')->orderBy('id')->get()->toArray());
            $this->assertSame($activity, DB::table('activity_log')->count());
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
        unset($rows[$index][$prefix.'_value']);
        $this->reject(fn () => $this->job($class, $root, $rows, $individual)->handle(app(ProcessLaboratoryResults::class)));
        $rows[$index][$prefix.'_value'] = 0;
        $this->job($class, $root, $rows, $individual)->handle(app(ProcessLaboratoryResults::class));
        $result = Models\Result::query()->where('sample_id', $root->sample_id)->where('parameter_id', $rows[$index]['parameter_id'])->firstOrFail();
        $this->assertSame('0', (string) $result->{$prefix.'_value'});
        $this->assertNotNull($result->{$prefix.'_date'});
    }

    #[DataProvider('reviewJobs')]
    public function test_review_requires_recorded_predecessor_values_and_dates(string $class, string $stage, bool $counter, bool $individual): void
    {
        [$root] = $this->fixture($this->lab, $counter);
        $rows = $this->rows($root, $stage);
        $result = Models\Result::query()->findOrFail($rows[$individual ? 0 : 1]['result_id']);
        $prefixes = $stage === 'approve' ? ['inserted', 'verified'] : ['inserted'];
        foreach ($prefixes as $prefix) {
            foreach (['_date' => null, '_value' => '  '] as $suffix => $value) {
                $field = $prefix.$suffix;
                $original = $result->{$field};
                $result->update([$field => $value]);
                $before = DB::table('results')->orderBy('id')->get()->toArray();
                $this->reject(fn () => $this->job($class, $root, $rows, $individual)->handle(app(ProcessLaboratoryResults::class)));
                $this->assertEquals($before, DB::table('results')->orderBy('id')->get()->toArray());
                $this->assertSame([], Storage::disk('public')->allFiles());
                $result->update([$field => $original]);
            }
        }
    }

    #[DataProvider('jobs')]
    public function test_approved_evidence_cannot_be_changed_by_any_ordinary_stage(string $class, string $stage, bool $counter, bool $individual): void
    {
        [$root] = $this->fixture($this->lab, $counter);
        $approvalRows = $this->rows($root, 'approve');
        $approvalClass = $counter ? Jobs\ApproveCounterAnalysisResults::class : Jobs\ApproveAnalysisResults::class;
        $this->job($approvalClass, $root, $approvalRows)->handle(app(ProcessLaboratoryResults::class));
        $before = DB::table('results')->orderBy('id')->get()->toArray();
        $files = Storage::disk('public')->allFiles();
        $activity = DB::table('activity_log')->count();
        $rows = $this->rows($root, $stage);
        $prefix = match ($stage) {
            'analyze' => 'inserted', 'verify' => 'verified', 'approve' => 'approved',
        };
        $index = $individual ? 0 : 1;
        $rows[$index][$prefix.'_value'] = '999';
        $this->reject(fn () => $this->job($class, $root, $rows, $individual)->handle(app(ProcessLaboratoryResults::class)));
        $this->assertEquals($before, DB::table('results')->orderBy('id')->get()->toArray());
        $this->assertSame($files, Storage::disk('public')->allFiles());
        $this->assertSame($activity, DB::table('activity_log')->count());
    }

    public function test_http_rejects_blank_values_and_skipped_verification_before_dispatch(): void
    {
        [$root] = $this->fixture($this->lab);
        $rows = $this->rows($root, 'approve');
        Bus::fake([Jobs\ApproveAnalysisResults::class]);
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
        $rows[1]['approved_value'] = ' ';
        $this->postJson(route('results.store'), ['action' => 'approve', 'sample_id' => $root->sample_id, 'results' => $rows, 'signature' => self::SIGNATURE])
            ->assertUnprocessable()->assertJsonValidationErrors('results.1.approved_value');
        $rows[1]['approved_value'] = '0';
        Models\Result::query()->findOrFail($rows[1]['result_id'])->update(['verified_date' => null]);
        $this->postJson(route('results.store'), ['action' => 'approve', 'sample_id' => $root->sample_id, 'results' => $rows, 'signature' => self::SIGNATURE])
            ->assertUnprocessable()->assertJsonValidationErrors('results.1.result_id');
        Bus::assertNotDispatched(Jobs\ApproveAnalysisResults::class);
    }

    #[DataProvider('persistenceFaults')]
    public function test_lifecycle_vetoes_and_changed_evidence_roll_back_the_entire_stage(string $class, string $stage, bool $counter, bool $individual, string $fault): void
    {
        [$root, $product, $entry] = $this->fixture($this->lab, $counter);
        $rows = $this->rows($root, $stage);
        $before = DB::table('results')->orderBy('id')->get()->toArray();
        $activity = DB::table('activity_log')->count();
        $rootBefore = $root->fresh()->getAttributes();
        $productBefore = $product->fresh()->getAttributes();
        $entryBefore = $entry->fresh()->getAttributes();
        if ($fault === 'veto') {
            Models\Result::saving(fn (): bool => false);
        } elseif ($fault === 'predecessor') {
            Models\Result::saving(function (Models\Result $result) use ($stage): void {
                $result->{$stage === 'approve' ? 'verified_value' : 'approved_value'} = 'Corrupted evidence';
            });
        } else {
            Models\ISOActivityLog::creating(function (Models\ISOActivityLog $audit) use ($fault, $root, $rows): ?bool {
                if ($fault === 'audit') {
                    return false;
                }
                if ($fault === 'extra_result') {
                    $attributes = DB::table('results')->where('sample_id', $root->sample_id)->first();
                    DB::table('results')->insert(Arr::except((array) $attributes, ['id']));

                    return null;
                }
                if ($fault === 'audit_identity') {
                    $audit->causer_id = null;

                    return null;
                }
                if ($fault === 'audit_content') {
                    $audit->description = 'Forged description';

                    return null;
                }
                DB::table('results')->where('sample_id', $root->sample_id)->where('parameter_id', $rows[0]['parameter_id'])
                    ->update(['inserted_value' => 'Corrupted evidence']);

                return null;
            });
        }
        try {
            $this->job($class, $root, $rows, $individual)->handle(app(ProcessLaboratoryResults::class));
            $this->fail('Expected vetoed or changed evidence to fail closed.');
        } catch (LogicException $exception) {
            $this->assertContains($exception->getMessage(), ['Result evidence was not persisted.',
                'Result audit was not persisted.', 'Persisted result evidence differs from the intended result.',
                'Persisted result set differs from the intended result set.', 'Persisted result audit differs from the intended audit.']);
        }
        $this->assertEquals($before, DB::table('results')->orderBy('id')->get()->toArray());
        $this->assertSame($activity, DB::table('activity_log')->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame($rootBefore, $root->fresh()->getAttributes());
        $this->assertSame($productBefore, $product->fresh()->getAttributes());
        $this->assertSame($entryBefore, $entry->fresh()->getAttributes());
    }

    /** @return array<string,array{class-string<Jobs\LaboratoryResultMutation>,string,bool,bool,string}> */
    public static function persistenceFaults(): array
    {
        $cases = [];
        foreach (self::jobs() as $label => $job) {
            foreach (['veto', 'predecessor', 'sibling', 'audit', 'extra_result', 'audit_identity', 'audit_content'] as $fault) {
                $cases[$label.' '.$fault] = [...$job, $fault];
            }
        }

        return $cases;
    }

    #[DataProvider('workflowParentFaults')]
    public function test_parent_save_faults_roll_back_the_entire_scientific_stage(string $class, string $stage, bool $counter, bool $individual, string $target, string $fault): void
    {
        [$root, $product, $entry] = $this->fixture($this->lab, $counter);
        $rows = $this->rows($root, $stage);
        if ($individual && $stage === 'approve') {
            Models\Result::query()->where('sample_id', $root->sample_id)->whereKeyNot($rows[0]['result_id'])
                ->update(['approved_value' => '1.5', 'approved_date' => now()]);
        }
        $record = match ($target) {
            'root' => $root, 'product' => $product, 'entry' => $entry,
        };
        $before = $this->workflowSnapshot();
        if ($fault === 'collateral') {
            $record::saved(function ($saved) use ($record): void {
                if ($saved->id === $record->id) {
                    DB::table($saved->getTable())->where('id', $saved->id)->update(['created_at' => '2001-01-01 00:00:00']);
                }
            });
        } else {
            $record::saving(function ($saving) use ($record, $target, $fault, $stage): ?bool {
                if ($saving->id !== $record->id) {
                    return null;
                }
                if ($fault === 'veto') {
                    return false;
                }
                $field = match ($target) {
                    'root' => $stage === 'analyze' ? 'init_date' : 'end_date',
                    'product' => 'sample_status', 'entry' => 'status',
                };
                $saving->{$field} = $target === 'root' ? '2001-01-01 00:00:00' : 'FORGED';

                return null;
            });
        }
        $this->expectWorkflowRollback(fn () => $this->job($class, $root, $rows, $individual)->handle(app(ProcessLaboratoryResults::class)));
        $this->assertSame($before, $this->workflowSnapshot());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public static function workflowParentFaults(): array
    {
        $cases = [];
        foreach (self::jobs() as $label => $job) {
            if ($job[1] === 'verify') {
                continue;
            }
            foreach ($job[2] ? ['root'] : ['root', 'product', 'entry'] as $target) {
                foreach (['veto', 'requested_value', 'collateral'] as $fault) {
                    $cases[$label.' '.$target.' '.$fault] = [...$job, $target, $fault];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('workflowGraphFaults')]
    public function test_result_audit_hooks_cannot_change_workflow_source_roots(string $class, string $stage, bool $counter, bool $individual, string $target): void
    {
        [$root, $product, $entry] = $this->fixture($this->lab, $counter);
        $rows = $this->rows($root, $stage);
        $record = match ($target) {
            'root' => $root, 'product' => $product, 'entry' => $entry,
            'sample' => $root->sample, 'code' => $root->code,
        };
        $before = $this->workflowSnapshot();
        Models\ISOActivityLog::creating(function (Models\ISOActivityLog $audit) use ($record): void {
            if ($audit->subject_type === (new Models\Result)->getMorphClass()) {
                DB::table($record->getTable())->where('id', $record->id)->update(['created_at' => '2001-01-01 00:00:00']);
            }
        });
        $this->expectWorkflowRollback(fn () => $this->job($class, $root, $rows, $individual)->handle(app(ProcessLaboratoryResults::class)));
        $this->assertSame($before, $this->workflowSnapshot());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public static function workflowGraphFaults(): array
    {
        $cases = [];
        foreach (self::jobs() as $label => $job) {
            foreach (['root', 'product', 'entry', 'sample', 'code'] as $target) {
                $cases[$label.' '.$target] = [...$job, $target];
            }
        }

        return $cases;
    }

    #[DataProvider('counterSourceFaults')]
    public function test_counter_completion_preserves_source_evidence_and_checks_the_requested_flag(string $fault): void
    {
        [$root] = $this->fixture($this->lab, true);
        $rows = $this->rows($root, 'approve');
        $source = $root->requested_result;
        if ($fault === 'archived_source_sibling') {
            DB::table('results')->where('sample_id', $source->sample_id)->where('id', '!=', $source->id)->update(['deleted_at' => now()]);
        }
        $before = $this->workflowSnapshot();
        if ($fault === 'source_veto') {
            Models\Result::saving(fn (Models\Result $result): ?bool => $result->id === $source->id ? false : null);
        } else {
            Models\CounterAnalysis::saved(function (Models\CounterAnalysis $record) use ($fault, $source, $root): void {
                if ($record->id !== $root->id) {
                    return;
                }
                if ($fault === 'source_evidence') {
                    DB::table('results')->where('id', $source->id)->update(['inserted_value' => 'FORGED']);
                } elseif (in_array($fault, ['source_sibling', 'source_sibling_archive', 'archived_source_sibling'], true)) {
                    DB::table('results')->where('sample_id', $source->sample_id)->where('id', '!=', $source->id)
                        ->update($fault === 'source_sibling_archive' ? ['deleted_at' => now()] : ['inserted_value' => 'FORGED']);
                } else {
                    DB::table('analysis')->where('id', $root->analysis_id)->update(['created_at' => '2001-01-01 00:00:00']);
                }
            });
        }
        $this->expectWorkflowRollback(fn () => $this->job(Jobs\ApproveCounterAnalysisResults::class, $root, $rows)->handle(app(ProcessLaboratoryResults::class)));
        $this->assertSame($before, $this->workflowSnapshot());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public static function counterSourceFaults(): array
    {
        return [['source_veto'], ['source_evidence'], ['source_analysis'], ['source_sibling'], ['source_sibling_archive'], ['archived_source_sibling']];
    }

    public function test_workflow_snapshot_query_count_does_not_grow_with_sibling_rows(): void
    {
        [$root] = $this->fixture($this->lab);
        $job = $this->job(Jobs\InsertAnalysisResults::class, $root, $this->rows($root, 'analyze'));
        $job->handle(app(ProcessLaboratoryResults::class));
        $countQueries = function () use ($job): int {
            $connection = DB::connection();
            $connection->flushQueryLog();
            $connection->enableQueryLog();
            try {
                $job->handle(app(ProcessLaboratoryResults::class));

                return count($connection->getQueryLog());
            } finally {
                $connection->disableQueryLog();
                $connection->flushQueryLog();
            }
        };
        $before = $countQueries();
        for ($index = 0; $index < 20; $index++) {
            $sample = Models\Sample::query()->create(['cl_id' => $root->cl_id, 'sample_month' => now()->format('y/m')]);
            Models\Analysis::query()->create(['cl_id' => $root->cl_id, 'sample_id' => $sample->id,
                'profile_id' => $root->profile_id, 'product_id' => $root->product_id,
                'department_id' => $root->department_id, 'type_id' => $root->type_id]);
        }
        $this->assertSame($before, $countQueries());
    }

    #[DataProvider('workflowGraphInsertions')]
    public function test_result_audits_cannot_append_unintended_workflow_children(string $child): void
    {
        [$root, $product] = $this->fixture($this->lab);
        $rows = $this->rows($root, 'analyze');
        $before = $this->workflowSnapshot();
        Models\ISOActivityLog::creating(function (Models\ISOActivityLog $audit) use ($child, $root, $product): void {
            if ($audit->subject_type !== (new Models\Result)->getMorphClass()) {
                return;
            }
            match ($child) {
                'code' => Models\LabCode::query()->create(['collection_id' => $product->id, 'cl_month' => now()->format('y/m')]),
                'sample' => Models\Sample::query()->create(['cl_id' => $root->cl_id, 'sample_month' => now()->format('y/m')]),
                'analysis' => Models\Analysis::query()->create(['cl_id' => $root->cl_id, 'sample_id' => $root->sample_id,
                    'profile_id' => $root->profile_id, 'product_id' => $root->product_id,
                    'department_id' => $root->department_id, 'type_id' => $root->type_id]),
            };
        });
        $this->expectWorkflowRollback(fn () => $this->job(Jobs\InsertAnalysisResults::class, $root, $rows)->handle(app(ProcessLaboratoryResults::class)));
        $this->assertSame($before, $this->workflowSnapshot());
    }

    public static function workflowGraphInsertions(): array
    {
        return [['code'], ['sample'], ['analysis']];
    }

    #[DataProvider('signedParentFailures')]
    public function test_approval_parent_failure_preserves_verification_signatures(bool $counter, string $target): void
    {
        [$root, $product, $entry] = $this->fixture($this->lab, $counter);
        $verifyClass = $counter ? Jobs\VerifyCounterAnalysisResults::class : Jobs\VerifyAnalysisResults::class;
        $this->job($verifyClass, $root, $this->rows($root, 'verify'))->handle(app(ProcessLaboratoryResults::class));
        Models\Result::query()->where('sample_id', $root->sample_id)->update(['verified_by_id' => $this->colleague->id]);
        $rows = $this->rows($root, 'approve');
        $record = match ($target) {
            'root' => $root, 'product' => $product, 'entry' => $entry
        };
        $before = $this->workflowSnapshot();
        $files = Storage::disk('public')->allFiles();
        $this->assertNotEmpty($files);
        $contents = collect($files)->mapWithKeys(fn (string $path): array => [$path => Storage::disk('public')->get($path)])->all();
        $record::saving(fn (Model $saving): ?bool => $saving->id === $record->id ? false : null);
        $approveClass = $counter ? Jobs\ApproveCounterAnalysisResults::class : Jobs\ApproveAnalysisResults::class;
        $this->expectWorkflowRollback(fn () => $this->job($approveClass, $root, $rows)->handle(app(ProcessLaboratoryResults::class)));
        $this->assertSame($before, $this->workflowSnapshot());
        $this->assertSame($files, Storage::disk('public')->allFiles());
        foreach ($contents as $path => $bytes) {
            $this->assertSame($bytes, Storage::disk('public')->get($path));
        }
    }

    public static function signedParentFailures(): array
    {
        return [[false, 'root'], [false, 'product'], [false, 'entry'], [true, 'root']];
    }

    #[DataProvider('signaturePersistenceFaults')]
    public function test_signature_persistence_faults_abort_review_and_preserve_prior_files(string $class, string $stage, bool $counter, bool $individual, string $fault): void
    {
        [$root] = $this->fixture($this->lab, $counter);
        $rows = $this->rows($root, $stage);
        $collection = $stage === 'verify' ? 'verification_signature' : 'approval_signature';
        foreach ($rows as $row) {
            Models\Result::query()->findOrFail($row['result_id'])->addMediaFromBase64(self::SIGNATURE)
                ->usingFileName('retained.png')->toMediaCollection($collection);
        }
        $before = $this->workflowSnapshot();
        $files = collect(Storage::disk('public')->allFiles())->mapWithKeys(fn (string $path): array => [$path => Storage::disk('public')->get($path)])->all();
        if ($fault === 'creation_veto') {
            Media::creating(fn (Media $media): ?bool => str_starts_with($media->collection_name, 'pending_result_signature_') ? false : null);
        } elseif (in_array($fault, ['late_media', 'late_file', 'late_retained_media'], true)) {
            Models\ISOActivityLog::creating(function (Models\ISOActivityLog $audit) use ($collection, $fault): void {
                if ($audit->properties->has('result_value')) {
                    if ($fault === 'late_file') {
                        $media = Media::query()->where('collection_name', $collection)->where('file_name', '!=', 'retained.png')->firstOrFail();
                        Storage::disk($media->disk)->put($media->getPathRelativeToRoot(), 'corrupted signature bytes');

                        return;
                    }
                    if ($fault === 'late_retained_media') {
                        DB::table('media')->where('file_name', 'retained.png')->update(['model_id' => 2147483647]);

                        return;
                    }
                    DB::table('media')->where('collection_name', $collection)->where('file_name', '!=', 'retained.png')->update(['file_name' => 'forged.png']);
                }
            });
        } else {
            Media::updating(function (Media $media) use ($fault, $collection): ?bool {
                if ($fault === 'retirement_veto' && str_starts_with($media->collection_name, 'retired_result_signature_')) {
                    return false;
                }
                if ($fault === 'retirement_identity' && str_starts_with($media->collection_name, 'retired_result_signature_')) {
                    $media->model_id = 2147483647;
                }
                if ($media->collection_name !== $collection || ! str_starts_with((string) $media->getRawOriginal('collection_name'), 'pending_result_signature_')) {
                    return null;
                }
                if ($fault === 'promotion_veto') {
                    return false;
                }
                if ($fault === 'promotion_identity') {
                    $media->model_id = 2147483647;
                }
                if ($fault === 'promotion_hash') {
                    $media->setCustomProperty('signature_sha256', 'forged');
                }

                return null;
            });
        }
        try {
            $this->job($class, $root, $rows, $individual)->handle(app(ProcessLaboratoryResults::class));
            $this->fail('Signature persistence faults must abort the entire scientific stage.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('signature', strtolower($exception->getMessage()));
        }
        $this->assertSame($before, $this->workflowSnapshot());
        $this->assertSame(array_keys($files), Storage::disk('public')->allFiles());
        foreach ($files as $path => $bytes) {
            $this->assertSame($bytes, Storage::disk('public')->get($path));
        }
    }

    public static function signaturePersistenceFaults(): array
    {
        $cases = [];
        foreach (self::reviewJobs() as $label => $job) {
            foreach (['creation_veto', 'promotion_veto', 'promotion_identity', 'promotion_hash', 'retirement_veto', 'retirement_identity', 'late_media', 'late_file', 'late_retained_media'] as $fault) {
                $cases[$label.' '.$fault] = [...$job, $fault];
            }
        }

        return $cases;
    }

    public function test_signature_cleanup_ignores_unpersisted_media_without_touching_prior_files(): void
    {
        Storage::disk('public')->put('retained.png', 'retained bytes');
        $unpersisted = new Media(['disk' => 'public', 'file_name' => 'retained.png']);
        app(LaboratoryResultSignatures::class)->discard([$unpersisted]);
        $this->assertSame(['retained.png'], Storage::disk('public')->allFiles());
        $this->assertSame('retained bytes', Storage::disk('public')->get('retained.png'));
    }

    #[DataProvider('counterSourceSignatureFaults')]
    public function test_counter_review_preserves_live_and_archived_original_signature_metadata(string $stage, bool $archivedSibling, string $fault): void
    {
        [$root] = $this->fixture($this->lab, true);
        $rows = $this->rows($root, $stage);
        $source = $archivedSibling
            ? Models\Result::query()->where('sample_id', $root->requested_result->sample_id)->whereKeyNot($root->result_id)->firstOrFail()
            : $root->requested_result;
        $sourceMedia = $source->addMediaFromBase64(self::SIGNATURE)->usingFileName('source.png')->toMediaCollection('verification_signature');
        if ($archivedSibling) {
            $source->delete();
        }
        $targetMedia = Models\Result::query()->findOrFail($rows[0]['result_id'])->addMediaFromBase64(self::SIGNATURE)
            ->usingFileName('target.png')->toMediaCollection($stage === 'verify' ? 'verification_signature' : 'approval_signature');
        $before = $this->workflowSnapshot();
        $files = collect(Storage::disk('public')->allFiles())->mapWithKeys(fn (string $path): array => [$path => Storage::disk('public')->get($path)])->all();
        $triggered = false;
        $corrupt = function () use ($sourceMedia, &$triggered): void {
            $triggered = true;
            DB::table('media')->where('id', $sourceMedia->id)->update(['file_name' => 'forged-source.png']);
        };
        if ($fault === 'read_metadata') {
            Media::retrieved(function (Media $media) use ($targetMedia, $corrupt): void {
                if ($media->id === $targetMedia->id) {
                    $corrupt();
                }
            });
        } else {
            Models\ISOActivityLog::creating(function (Models\ISOActivityLog $audit) use ($corrupt): void {
                if ($audit->properties->has('result_value')) {
                    $corrupt();
                }
            });
        }
        try {
            $this->job($stage === 'verify' ? Jobs\VerifyCounterAnalysisResults::class : Jobs\ApproveCounterAnalysisResults::class, $root, $rows)
                ->handle(app(ProcessLaboratoryResults::class));
            $this->fail('Counter review must preserve the original source signature evidence.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('signature', strtolower($exception->getMessage()));
        }
        $this->assertTrue($triggered);
        $this->assertSame($before, $this->workflowSnapshot());
        $this->assertSame(array_keys($files), Storage::disk('public')->allFiles());
        foreach ($files as $path => $bytes) {
            $this->assertSame($bytes, Storage::disk('public')->get($path));
        }
    }

    public static function counterSourceSignatureFaults(): array
    {
        $cases = [];
        foreach (['verify', 'approve'] as $stage) {
            foreach ([false, true] as $archivedSibling) {
                foreach (['read_metadata', 'late_metadata'] as $fault) {
                    $cases[$stage.'-'.($archivedSibling ? 'archived' : 'live').'-'.$fault] = [$stage, $archivedSibling, $fault];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('counterSourceReadStages')]
    public function test_counter_source_baseline_is_inert_and_late_audit_reads_remain_guarded(string $stage): void
    {
        [$root] = $this->fixture($this->lab, true);
        $rows = $this->rows($root, $stage);
        $sourceMedia = $root->requested_result->addMediaFromBase64(self::SIGNATURE)->usingFileName('source.png')->toMediaCollection('verification_signature');
        $before = (array) DB::table('media')->where('id', $sourceMedia->id)->first();
        $workflowBefore = $this->workflowSnapshot();
        $reads = 0;
        Models\Result::retrieved(function (Models\Result $result) use ($root, $sourceMedia, &$reads): void {
            if ($result->id === $root->result_id) {
                $reads++;
                DB::table('media')->where('id', $sourceMedia->id)->update(['file_name' => 'read-hook-corruption.png']);
            }
        });
        $attempt = fn () => $this->job($stage === 'verify' ? Jobs\VerifyCounterAnalysisResults::class : Jobs\ApproveCounterAnalysisResults::class, $root, $rows)
            ->handle(app(ProcessLaboratoryResults::class));
        if ($stage === 'approve') {
            try {
                $attempt();
                $this->fail('The late source audit read must not replace signature evidence.');
            } catch (LogicException $exception) {
                $this->assertStringContainsString('signature', strtolower($exception->getMessage()));
            }
            $this->assertGreaterThan(0, $reads);
            $this->assertSame($workflowBefore, $this->workflowSnapshot());
        } else {
            $attempt();
            $this->assertSame(0, $reads);
        }
        $this->assertSame($before, (array) DB::table('media')->where('id', $sourceMedia->id)->first());
        $this->assertSame(base64_decode(explode(',', self::SIGNATURE)[1]), Storage::disk($sourceMedia->disk)->get($sourceMedia->getPathRelativeToRoot()));
    }

    public static function counterSourceReadStages(): array
    {
        return [['verify'], ['approve']];
    }

    #[DataProvider('jobs')]
    public function test_final_workflow_checks_do_not_dispatch_retrieval_hooks(string $class, string $stage, bool $counter, bool $individual): void
    {
        [$root] = $this->fixture($this->lab, $counter);
        $job = $this->job($class, $root, $this->rows($root, $stage), $individual);
        $job->handle(app(ProcessLaboratoryResults::class));
        $before = $this->workflowSnapshot();
        $retrievals = 0;
        Models\Sample::retrieved(function (Models\Sample $sample) use ($root, &$retrievals): void {
            if ($sample->id === $root->sample_id && ++$retrievals === 6) {
                DB::table($root->getTable())->where('id', $root->id)->update(['created_at' => '2001-01-01 00:00:00']);
            }
        });
        $job->handle(app(ProcessLaboratoryResults::class));
        $this->assertSame(2, $retrievals);
        $this->assertSame($before, $this->workflowSnapshot());
    }

    #[DataProvider('jobs')]
    public function test_unchanged_replay_checks_workflow_after_the_final_authority_reload(string $class, string $stage, bool $counter, bool $individual): void
    {
        [$root] = $this->fixture($this->lab, $counter);
        $job = $this->job($class, $root, $this->rows($root, $stage), $individual);
        $job->handle(app(ProcessLaboratoryResults::class));
        $before = $this->workflowSnapshot();
        $reloads = 0;
        Models\User::retrieved(function (Models\User $user) use ($root, &$reloads): void {
            if ($user->id === $this->operator->id && ++$reloads === 2) {
                DB::table($root->getTable())->where('id', $root->id)->update(['created_at' => '2001-01-01 00:00:00']);
            }
        });
        $this->expectWorkflowRollback(fn () => $job->handle(app(ProcessLaboratoryResults::class)));
        $this->assertSame($before, $this->workflowSnapshot());
    }

    #[DataProvider('jobs')]
    public function test_unchanged_replay_still_rechecks_current_membership(string $class, string $stage, bool $counter, bool $individual): void
    {
        [$root] = $this->fixture($this->lab, $counter);
        $job = $this->job($class, $root, $this->rows($root, $stage), $individual);
        $job->handle(app(ProcessLaboratoryResults::class));
        $before = $this->workflowSnapshot();
        $files = Storage::disk('public')->allFiles();
        Models\Result::retrieved(function (): void {
            DB::table('lab_user')->where('lab_id', $this->lab->id)->where('user_id', $this->operator->id)->delete();
        });
        $this->reject(fn () => $job->handle(app(ProcessLaboratoryResults::class)));
        $this->assertSame($before, $this->workflowSnapshot());
        $this->assertSame($files, Storage::disk('public')->allFiles());
    }

    private function expectWorkflowRollback(callable $attempt): void
    {
        try {
            $attempt();
            $this->fail('A failed workflow parent write must abort the complete scientific stage.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('workflow', strtolower($exception->getMessage()));
        }
    }

    /** @return array<string,list<array<string,mixed>>> */
    private function workflowSnapshot(): array
    {
        $snapshot = [];
        foreach (['analysis', 'counter_analysis', 'collection_product', 'sample_entries', 'samples', 'lab_codes', 'results', 'activity_log', 'media'] as $table) {
            $snapshot[$table] = DB::table($table)->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
        }
        $snapshot['membership'] = DB::table('lab_user')->orderBy('lab_id')->orderBy('user_id')->get()->map(fn (object $row): array => (array) $row)->all();

        return $snapshot;
    }

    #[DataProvider('jobs')]
    public function test_audit_preserves_literal_placeholder_text(string $class, string $stage, bool $counter, bool $individual): void
    {
        $label = 'Literal :subject.id :causer.name :properties.lab_code';
        [$root] = $this->fixture($this->lab, $counter, $label);
        $rows = $this->rows($root, $stage);
        Models\Result::query()->where('sample_id', $root->sample_id)->update(['parameter_label' => $label]);
        $this->job($class, $root, $rows, $individual)->handle(app(ProcessLaboratoryResults::class));
        $audit = Models\ISOActivityLog::query()->where('subject_type', (new Models\Result)->getMorphClass())
            ->where('properties->parameter_label', $label)->latest('id')->firstOrFail();
        $this->assertStringContainsString($label, $audit->description);
        $this->assertSame($label, $audit->properties->get('parameter_label'));
        $this->assertSame($this->operator->id, (int) $audit->causer_id);
    }

    /** @return array<string,array{class-string<Jobs\LaboratoryResultMutation>,string,bool,bool}> */
    public static function reviewJobs(): array
    {
        return array_filter(self::jobs(), fn (array $job): bool => $job[1] !== 'analyze');
    }

    /** @return array<string,array{class-string<Jobs\LaboratoryResultMutation>,string,bool,bool}> */
    public static function jobs(): array
    {
        return ['insert analysis' => [Jobs\InsertAnalysisResults::class, 'analyze', false, false],
            'verify analysis' => [Jobs\VerifyAnalysisResults::class, 'verify', false, false],
            'approve analysis' => [Jobs\ApproveAnalysisResults::class, 'approve', false, false],
            'insert counter' => [Jobs\InsertCounterAnalysisResults::class, 'analyze', true, false],
            'verify counter' => [Jobs\VerifyCounterAnalysisResults::class, 'verify', true, false],
            'approve counter' => [Jobs\ApproveCounterAnalysisResults::class, 'approve', true, false],
            'insert individual' => [Jobs\InsertIndividualResult::class, 'analyze', false, true],
            'verify individual' => [Jobs\VerifyIndividualResult::class, 'verify', false, true],
            'approve individual' => [Jobs\ApproveIndividualResult::class, 'approve', false, true]];
    }

    /** @return array<string,array{class-string<Jobs\LaboratoryResultMutation>,string,bool,bool,string}> */
    public static function invalidJobs(): array
    {
        $cases = [];
        foreach (self::jobs() as $label => $job) {
            foreach (['removed_member', 'inactive_actor', 'unverified_actor', 'revoked_permission', 'foreign_root', 'deleted_entry', 'ambiguous_owner'] as $change) {
                $cases[$label.' '.$change] = [...$job, $change];
            }
        }

        return $cases;
    }

    /** @return array{Models\Analysis|Models\CounterAnalysis,Models\CollectionProduct,Models\VAPSampleEntry} */
    /** @param array<string, mixed> $definition extra profile-definition (pivot) attributes for every parameter */
    private function fixture(Models\VAPLab $lab, bool $counter = false, ?string $parameterLabel = null, array $definition = []): array
    {
        $customer = Models\Customer::query()->create(['name' => 'Result customer']);
        $matrix = Models\Matrix::query()->create(['code' => fake()->unique()->bothify('M-########')]);
        $product = Models\Product::query()->create(['name' => 'Result product', 'matrix_id' => $matrix->id]);
        $collection = Models\Collection::query()->create(['customer_id' => $customer->id]);
        $cp = Models\CollectionProduct::query()->create(['customer_id' => $customer->id, 'product_id' => $product->id, 'collection_id' => $collection->id]);
        $department = Models\Department::factory()->create();
        $category = Models\AnalysisCategory::query()->create(['name' => fake()->unique()->bothify('Category #######'), 'code' => fake()->unique()->bothify('AC-#######'), 'department_id' => $department->id]);
        $profile = Models\Profile::query()->create(['name' => fake()->unique()->bothify('Profile #######'), 'code' => fake()->unique()->bothify('PR-#######'), 'category_id' => $category->id]);
        $refs = [];
        foreach (['unit' => Models\Unit::class, 'protocol' => Models\Protocol::class, 'standard' => Models\Standard::class, 'nwp' => Models\NormativeWorkProcedure::class] as $key => $class) {
            $ref = $class::query()->create(['name' => fake()->unique()->bothify($key.' #######'), 'code' => fake()->unique()->bothify('REF-########')]);
            $refs[$key.'_id'] = $ref->id;
            $refs[$key.'_label'] = $ref->code;
        }
        $resultCategory = Models\ResultCategory::query()->create(['name' => fake()->unique()->bothify('Result category #######')]);
        for ($index = 0; $index < 2; $index++) {
            $parameter = Models\Parameter::query()->create(['name' => $parameterLabel ?? fake()->unique()->bothify('Parameter #######'), 'code' => fake()->unique()->bothify('PAR-#######'), 'active' => true]);
            $profile->parameters()->attach($parameter->id, [...$refs, 'category_id' => $resultCategory->id, 'category_label' => $resultCategory->name, ...$definition]);
        }
        $matrix->profiles()->attach($profile);
        $payload = app(PrepareSampleEntryPayload::class)->execute([
            'lab_id' => $lab->id, 'customer_id' => $customer->id, 'department_id' => $department->id,
            'client_submitted_info' => ['product_id' => $product->id, 'requested_profile_ids' => [$profile->id]],
        ], null);
        $entry = Models\VAPSampleEntry::factory()->create([...$payload, 'collection_product_id' => $cp->id]);
        $code = Models\LabCode::query()->create(['collection_id' => $cp->id, 'cl_month' => now()->format('y/m')]);
        $sample = Models\Sample::query()->create(['cl_id' => $code->id, 'sample_month' => now()->format('y/m')]);
        $root = Models\Analysis::query()->create(['cl_id' => $code->id, 'sample_id' => $sample->id, 'profile_id' => $profile->id,
            'product_id' => $product->id, 'department_id' => $department->id, 'type_id' => $category->id]);
        if ($counter) {
            $this->rows($root, 'verify');
            $root = app(RequestLaboratoryCounterAnalysis::class)->execute($lab->id,
                Models\Result::query()->where('sample_id', $sample->id)->firstOrFail()->id, $this->operator->id);
        }

        return [$root, $cp, $entry];
    }

    /** @return array<int,array<string,mixed>> */
    private function rows(Models\Analysis|Models\CounterAnalysis $root, string $stage): array
    {
        $rows = [];
        foreach ($root->profile->parameters as $parameter) {
            $row = ['parameter_id' => $parameter->id, 'sample_id' => $root->sample_id, 'profile_id' => $root->profile_id, 'code_id' => $root->cl_id,
                'collection_id' => $root->code->collection_id, 'product_id' => $root->code->collection->product_id, 'matrix_id' => $root->code->collection->product->matrix_id,
                'type_id' => $parameter->pivot->category_id, 'unit_id' => $parameter->pivot->unit_id, 'protocol_id' => $parameter->pivot->protocol_id,
                'standard_id' => $parameter->pivot->standard_id, 'nwp_id' => $parameter->pivot->nwp_id,
                'inserted_value' => '9.9', 'verified_value' => '2.5', 'approved_value' => '3.5', 'uncertainty_value' => '0.1',
                'insertion_notes' => 'Insertion observation', 'verification_notes' => 'Verification observation', 'approval_notes' => 'Approval observation',
                'inserted_by_id' => $this->operator->id, 'verified_by_id' => $this->operator->id, 'approved_by_id' => $this->operator->id,
                'inserted_by' => 'Forged actor', 'verified_by' => 'Forged actor', 'approved_by' => 'Forged actor',
                'inserted_date' => '2000-01-01 00:00:00', 'verified_date' => '2000-01-01 00:00:00', 'approved_date' => '2000-01-01 00:00:00',
                'count' => true, 'status' => false, 'requested_counter_analysis' => false,
                'extra_data' => ['display_format' => 'scientific', 'provenance' => 'Forged provenance']];
            if ($stage !== 'analyze') {
                $result = Models\Result::query()->firstOrCreate(['sample_id' => $root->sample_id, 'parameter_id' => $parameter->id], array_replace($row,
                    ['inserted_value' => '1.25', 'inserted_by_id' => $this->colleague->id, 'inserted_by' => 'Original technician', 'inserted_date' => now()->subDays(2),
                        'verified_value' => $stage === 'approve' ? '1.5' : null, 'verified_by' => $stage === 'approve' ? 'Original verifier' : null,
                        'verified_by_id' => $stage === 'approve' ? $this->colleague->id : null,
                        'verified_date' => $stage === 'approve' ? now()->subDay() : null, 'approved_value' => null, 'approved_date' => null,
                        'extra_data' => ['provenance' => 'Original provenance', 'display_format' => 'scientific'],
                        'resultable_id' => $root->id, 'resultable_type' => $root->getMorphClass()]));
                $row['result_id'] = $result->id;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /** @param class-string<Jobs\LaboratoryResultMutation> $class @param array<int,array<string,mixed>> $rows */
    private function job(string $class, Models\Analysis|Models\CounterAnalysis $root, array $rows, bool $individual = false): Jobs\LaboratoryResultMutation
    {
        return new $class($individual ? $rows[0] : $rows, $root->id, $this->operator, $this->lab->id, self::SIGNATURE);
    }

    private function reject(callable $attempt): void
    {
        try {
            $attempt();
            $this->fail('Expected mutation to fail closed.');
        } catch (AuthorizationException|ModelNotFoundException|ValidationException|HttpException) {
            $this->addToAssertionCount(1);
        }
    }

    private function failsPersistence(callable $attempt): void
    {
        try {
            $attempt();
            $this->fail('Expected persistence failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Second result failed', $exception->getMessage());
        }
    }
}
