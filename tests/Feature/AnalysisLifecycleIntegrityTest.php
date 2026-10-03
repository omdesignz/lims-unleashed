<?php

namespace Tests\Feature;

use App\Actions\PrepareSampleEntryPayload;
use App\Actions\ProcessLaboratoryResults;
use App\Events\AnalysisResultsApproved;
use App\Events\AnalysisResultsValidated;
use App\Jobs\ApproveAnalysisResults;
use App\Jobs\PlaceProductsInAnalysis;
use App\Listeners\GenerateAnalysisReportDocument;
use App\Models\Analysis;
use App\Models\AnalysisCategory;
use App\Models\Collection;
use App\Models\CollectionEndResult;
use App\Models\CollectionProduct;
use App\Models\Customer;
use App\Models\Department;
use App\Models\LabCode;
use App\Models\Matrix;
use App\Models\NormativeWorkProcedure;
use App\Models\Parameter;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Profile;
use App\Models\ProgrammedCollection;
use App\Models\Protocol;
use App\Models\QualityCertificate;
use App\Models\Result;
use App\Models\ResultCategory;
use App\Models\Role;
use App\Models\Sample;
use App\Models\Standard;
use App\Models\Unit;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Support\SampleEntryCollectionFlowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnalysisLifecycleIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    private VAPLab $laboratory;

    public function test_canonical_collection_flows_preserve_accession_metadata(): void
    {
        $user = $this->verifiedAdmin();
        $fixture = $this->collectionProductFixture();
        $source = $fixture['collectionProduct'];

        foreach (['direct', 'programmed'] as $type) {
            $entry = VAPSampleEntry::factory()->create([
                'lab_id' => $this->laboratory->id,
                'customer_id' => $source->customer_id,
                'warehouse_id' => $source->warehouse_id,
                'department_id' => $fixture['department']->id,
                'received_by_id' => $user->id,
                'status' => 'POR_INICIAR',
                'client_submitted_info' => [
                    'product_id' => $source->product_id,
                    'requested_profile_ids' => [$fixture['profile']->id],
                    'collection_type' => $type,
                    'collection_location' => 'Sala de receção QA',
                    'vehicle_reference' => 'VIATURA-QA',
                    'sampling_plan_ref' => 'PLANO-QA-2026',
                    'customer_submitted_info' => 'Frasco selado e cadeia de custódia confirmada.',
                ],
            ]);
            $record = DB::transaction(fn () => app(SampleEntryCollectionFlowService::class)->sync($entry));

            $this->assertNotNull($record);
            $this->assertAccessionMetadata($record);
            $this->assertSame($type, $record->collection->collectionable_type);
            $this->assertSame($entry->id, $record->sampleEntry->id);
            $this->assertSame($this->laboratory->id, $record->sampleEntry->lab_id);
            $this->assertSame(1, $record->samples()->count());
        }
    }

    public function test_programmed_analysis_placement_is_a_scoped_post_action(): void
    {
        $user = $this->verifiedAdmin();
        $source = $this->collectionProductFixture()['collectionProduct'];
        $programmedCollection = ProgrammedCollection::query()->create([
            'user_id' => $user->id,
            'col_date' => now()->toDateString(),
            'placed_analysis' => false,
            'status' => false,
        ]);
        $collection = $programmedCollection->collection()->save(new Collection([
            'customer_id' => $source->customer_id,
            'warehouse_id' => $source->warehouse_id,
        ]));
        $collectionProduct = CollectionProduct::query()->create([
            'collection_id' => $collection->id,
            'customer_id' => $source->customer_id,
            'warehouse_id' => $source->warehouse_id,
            'product_id' => $source->product_id,
            'result_id' => $source->result_id,
        ]);
        VAPSampleEntry::factory()->create([
            'lab_id' => $this->laboratory->id,
            'customer_id' => $source->customer_id,
            'collection_product_id' => $collectionProduct->id,
        ]);
        LabCode::query()->create([
            'code' => '',
            'codeable_type' => 'analysis',
            'cl_month' => now()->format('y/m'),
            'collection_id' => $collectionProduct->id,
        ]);
        Bus::fake();

        $this->actingAs($user)
            ->post(route('programmedcollections.PlaceProductsInAnalysis', $collectionProduct))
            ->assertRedirect();

        Bus::assertDispatched(PlaceProductsInAnalysis::class, fn (PlaceProductsInAnalysis $job): bool => (int) $job->collection_product_id === $collectionProduct->id
            && $job->user_id === $user->id && $job->lab_id === $this->laboratory->id && $job->afterCommit
        );

        $this->actingAs($user)
            ->post(route('programmedcollections.PlaceProductsInAnalysis', $source))
            ->assertNotFound();
    }

    public function test_result_validation_rejects_cross_sample_lineage(): void
    {
        $user = $this->verifiedAdmin();
        $fixture = $this->collectionProductFixture();
        $result = $fixture['result'];
        $otherSample = Sample::query()->create([
            'cl_id' => $fixture['code']->id,
            'sample_month' => now()->format('y/m'),
        ]);
        Analysis::query()->create([
            'cl_id' => $fixture['code']->id,
            'sample_id' => $otherSample->id,
            'profile_id' => $fixture['profile']->id,
            'product_id' => $fixture['product']->id,
            'department_id' => $fixture['department']->id,
            'type_id' => $fixture['category']->id,
        ]);

        $this->actingAs($user)
            ->from(route('analysis.index', ['category' => 'approve']))
            ->post(route('results.store'), [
                'action' => 'approve',
                'sample_id' => $this->option($otherSample->id),
                'results' => [$this->resultPayload($result)],
                'signature' => 'data:image/png;base64,aW50ZWdyaXR5',
            ])
            ->assertSessionHasErrors('results.0.sample_id');
    }

    public function test_approval_completes_the_collection_and_emits_certificate_event_once(): void
    {
        Storage::fake('public');
        config(['media-library.disk_name' => 'public']);
        $user = $this->verifiedAdmin();
        $fixture = $this->collectionProductFixture();
        $collectionProduct = $fixture['collectionProduct'];
        $entry = $collectionProduct->sampleEntry;
        $analysis = $fixture['analysis'];
        $result = $fixture['result'];
        Event::fake([AnalysisResultsApproved::class, AnalysisResultsValidated::class]);

        $job = new ApproveAnalysisResults([['result_id' => $result->id, 'parameter_id' => $result->parameter_id,
            'approved_value' => '1.5', 'uncertainty_value' => '0.1']], $analysis->id, $user->id, $this->laboratory->id,
            'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9sX6lz4AAAAASUVORK5CYII=');
        $job->handle(app(ProcessLaboratoryResults::class));
        $collectionProduct->refresh();
        $this->assertTrue($collectionProduct->status);
        $this->assertTrue($collectionProduct->processed);
        $this->assertSame('Concluída', $collectionProduct->sample_status);
        $this->assertNotNull($collectionProduct->analysis_end_date);
        $this->assertSame('COMPLETADO', $entry->fresh()->status);
        Event::assertDispatchedTimes(AnalysisResultsValidated::class, 1);
        $job->handle(app(ProcessLaboratoryResults::class));
        Event::assertDispatchedTimes(AnalysisResultsValidated::class, 1);
    }

    public function test_report_certificate_generation_is_idempotent(): void
    {
        $user = $this->verifiedAdmin();
        $result = $this->collectionProductFixture()['result'];
        $collectionProduct = $result->code->collection;
        $listener = new GenerateAnalysisReportDocument;
        $event = new AnalysisResultsValidated($result, $user->id);

        $listener->handle($event);
        $listener->handle($event);

        $this->assertSame(
            1,
            QualityCertificate::query()->where('collection_id', $collectionProduct->id)->count()
        );
    }

    public function test_lifecycle_schema_columns_are_managed_by_migrations(): void
    {
        $this->assertTrue(Schema::hasColumns('collection_product', [
            'sample_status',
            'sampling_plan_ref',
            'customer_submitted_info',
            'collected_by_lab',
            'analysis_start_date',
            'analysis_end_date',
        ]));
        $this->assertTrue(Schema::hasColumns('quality_certificates', [
            'product_id',
            'validated_by',
            'validated_by_id',
            'validated_at',
        ]));
    }

    private function verifiedAdmin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        foreach (['add_analysis', 'approve_results'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->laboratory = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $this->laboratory->id, 'user_id' => $user->id]);
        $this->withSession(['active_lab_id' => $this->laboratory->id]);

        return $user;
    }

    /**
     * @return array{collectionProduct: CollectionProduct, code: LabCode, analysis: Analysis, result: Result, product: Product, profile: Profile, department: Department, category: AnalysisCategory}
     */
    private function collectionProductFixture(): array
    {
        $customer = Customer::query()->create(['name' => fake()->unique()->company()]);
        $warehouse = Warehouse::query()->create(['name' => fake()->unique()->company(), 'customer_id' => $customer->id]);
        $matrix = Matrix::query()->create(['code' => fake()->unique()->bothify('LC-M-######')]);
        $product = Product::query()->create(['name' => 'Lifecycle product', 'matrix_id' => $matrix->id]);
        $department = Department::factory()->create();
        $category = AnalysisCategory::query()->create([
            'name' => fake()->unique()->bothify('Lifecycle category #######'),
            'code' => fake()->unique()->bothify('LC-#######'),
            'department_id' => $department->id,
        ]);
        $profile = Profile::query()->create([
            'name' => fake()->unique()->bothify('Lifecycle profile #######'),
            'code' => fake()->unique()->bothify('LP-#######'),
            'category_id' => $category->id,
        ]);
        $matrix->profiles()->attach($profile->id);
        $parameter = Parameter::query()->create(['name' => fake()->unique()->bothify('Lifecycle parameter #######'), 'active' => true]);
        $unit = Unit::query()->create(['name' => 'Lifecycle unit', 'code' => fake()->unique()->bothify('U-######')]);
        $protocol = Protocol::query()->create(['name' => 'Lifecycle protocol', 'code' => fake()->unique()->bothify('P-######')]);
        $standard = Standard::query()->create(['name' => 'Lifecycle standard', 'code' => fake()->unique()->bothify('S-######')]);
        $nwp = NormativeWorkProcedure::query()->create(['name' => 'Lifecycle procedure', 'code' => fake()->unique()->bothify('N-######')]);
        $resultCategory = ResultCategory::query()->create(['name' => 'Lifecycle result category']);
        $profile->parameters()->attach($parameter->id, [
            'unit_id' => $unit->id, 'protocol_id' => $protocol->id, 'standard_id' => $standard->id,
            'nwp_id' => $nwp->id, 'category_id' => $resultCategory->id,
        ]);
        $endResult = CollectionEndResult::query()->create(['name' => 'Lifecycle end result']);
        $collection = Collection::query()->create(['customer_id' => $customer->id, 'warehouse_id' => $warehouse->id]);
        $collectionProduct = CollectionProduct::query()->create([
            'collection_id' => $collection->id, 'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id,
            'product_id' => $product->id, 'result_id' => $endResult->id,
        ]);
        $entryPayload = app(PrepareSampleEntryPayload::class)->execute([
            'lab_id' => $this->laboratory->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'department_id' => $department->id,
            'client_submitted_info' => [
                'product_id' => $product->id,
                'requested_profile_ids' => [$profile->id],
            ],
        ], null);
        VAPSampleEntry::factory()->create([...$entryPayload, 'collection_product_id' => $collectionProduct->id]);
        $code = LabCode::query()->create(['collection_id' => $collectionProduct->id, 'cl_month' => now()->format('y/m')]);
        $sample = Sample::query()->create(['cl_id' => $code->id, 'sample_month' => now()->format('y/m')]);
        $analysis = Analysis::query()->create([
            'cl_id' => $code->id, 'sample_id' => $sample->id, 'profile_id' => $profile->id,
            'product_id' => $product->id, 'department_id' => $department->id, 'type_id' => $category->id,
        ]);
        $result = Result::query()->create([
            'sample_id' => $sample->id, 'product_id' => $product->id, 'parameter_id' => $parameter->id,
            'code_id' => $code->id, 'profile_id' => $profile->id, 'matrix_id' => $matrix->id,
            'collection_id' => $collectionProduct->id, 'type_id' => $resultCategory->id,
            'unit_id' => $unit->id, 'nwp_id' => $nwp->id, 'protocol_id' => $protocol->id,
            'standard_id' => $standard->id, 'inserted_date' => now()->subDays(2),
            'verified_date' => now()->subDay(), 'inserted_value' => '1.25', 'verified_value' => '1.5',
            'resultable_id' => $analysis->id, 'resultable_type' => $analysis->getMorphClass(),
        ]);

        return compact('collectionProduct', 'code', 'analysis', 'result', 'product', 'profile', 'department', 'category');
    }

    private function assertAccessionMetadata(CollectionProduct $record): void
    {
        $this->assertSame('POR_INICIAR', $record->sample_status);
        $this->assertSame('PLANO-QA-2026', $record->sampling_plan_ref);
        $this->assertSame('Frasco selado e cadeia de custódia confirmada.', $record->customer_submitted_info);
    }

    /**
     * @return array<string, mixed>
     */
    private function resultPayload(Result $result): array
    {
        return [
            'result_id' => $result->id,
            'approved_by' => null,
            'approved_by_id' => null,
            'verified_by_id' => $result->verified_by_id,
            'approved_date' => null,
            'verified_date' => $result->verified_date,
            'approved_value' => $result->approved_value ?? $result->verified_value ?? '1',
            'approval_notes' => null,
            'collection_id' => $result->collection_id,
            'count' => (bool) $result->count,
            'inserted_by' => $result->inserted_by,
            'inserted_by_id' => $result->inserted_by_id,
            'inserted_date' => $result->inserted_date,
            'inserted_value' => $result->inserted_value,
            'verified_by' => $result->verified_by,
            'verified_value' => $result->verified_value,
            'matrix_id' => $result->matrix_id,
            'max_ref_value' => $result->max_ref_value,
            'min_ref_value' => $result->min_ref_value,
            'parameter_id' => $this->option($result->parameter_id),
            'product_id' => $this->option($result->product_id),
            'protocol_id' => $this->option($result->protocol_id),
            'profile_id' => $result->profile_id,
            'unit_id' => $this->option($result->unit_id),
            'standard_id' => $this->option($result->standard_id),
            'code_id' => $this->option($result->code_id),
            'nwp_id' => $this->option($result->nwp_id),
            'requested_counter_analysis' => (bool) $result->requested_counter_analysis,
            'sample_id' => $result->sample_id,
            'status' => (bool) $result->status,
            'type_id' => $this->option($result->type_id),
            'result_is_qualitative' => false,
            'result_options' => [],
            'uncertainty_value' => $result->uncertainty_value,
            'sumC' => $result->sumC,
            'volume' => $result->volume,
            'n1' => $result->n1,
            'n2' => $result->n2,
            'dilution' => $result->dilution,
            'd1' => $result->d1,
            'd2' => $result->d2,
            'cfu1' => $result->cfu1,
            'cfu2' => $result->cfu2,
            'is_calculated' => (bool) $result->is_calculated,
            'is_override' => (bool) $result->is_override,
            'calculation_metadata' => $result->calculation_metadata,
            'extra_data' => $result->extra_data?->all() ?? [],
            'display_format' => 'standard',
        ];
    }

    /**
     * @return array{value: int, label: string}
     */
    private function option(int $value): array
    {
        return ['value' => $value, 'label' => (string) $value];
    }
}
