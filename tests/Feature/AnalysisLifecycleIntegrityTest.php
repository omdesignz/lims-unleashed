<?php

namespace Tests\Feature;

use App\Events\AnalysisResultsApproved;
use App\Events\AnalysisResultsValidated;
use App\Events\CollectionProcessed;
use App\Jobs\ApproveAnalysisResults;
use App\Jobs\PlaceProductsInAnalysis;
use App\Jobs\ProcessDirectCollectionProducts;
use App\Jobs\ProcessProgrammedCollectionProducts;
use App\Listeners\GenerateAnalysisReportDocument;
use App\Models\Analysis;
use App\Models\Collection;
use App\Models\CollectionProduct;
use App\Models\LabCode;
use App\Models\ProgrammedCollection;
use App\Models\QualityCertificate;
use App\Models\Result;
use App\Models\Role;
use App\Models\Sample;
use App\Models\User;
use App\Support\LaboratoryWorkflowNotifier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class AnalysisLifecycleIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_collection_jobs_preserve_accession_metadata(): void
    {
        Event::fake([CollectionProcessed::class]);

        $user = $this->verifiedAdmin();
        $source = CollectionProduct::query()
            ->whereNotNull(['customer_id', 'warehouse_id', 'product_id', 'result_id'])
            ->whereHas('product.matrix.profiles')
            ->with('customer')
            ->firstOrFail();
        $payload = $this->collectionProductPayload($source, [
            'sample_status' => 'Recebida para controlo',
            'sampling_plan_ref' => 'PLANO-QA-2026',
            'customer_submitted_info' => 'Frasco selado e cadeia de custódia confirmada.',
        ]);

        $directStartingId = (int) CollectionProduct::query()->max('id');
        (new ProcessDirectCollectionProducts(
            $source->customer_id,
            $source->warehouse_id,
            [$payload],
            now()->toDateString(),
            [],
            [],
            $user,
            $source->customer
        ))->handle();

        $directRecord = CollectionProduct::query()->where('id', '>', $directStartingId)->firstOrFail();
        $this->assertAccessionMetadata($directRecord);

        $programmedStartingId = (int) CollectionProduct::query()->max('id');
        (new ProcessProgrammedCollectionProducts(
            $source->customer_id,
            $source->warehouse_id,
            [$payload],
            now()->toDateString(),
            'Sala de receção QA',
            [],
            [],
            $user,
            $source->customer,
            'VIATURA-QA'
        ))->handle();

        $programmedRecord = CollectionProduct::query()->where('id', '>', $programmedStartingId)->firstOrFail();
        $this->assertAccessionMetadata($programmedRecord);
    }

    public function test_programmed_analysis_placement_is_a_scoped_post_action(): void
    {
        Bus::fake();

        $user = $this->verifiedAdmin();
        $source = CollectionProduct::query()
            ->whereNotNull(['customer_id', 'warehouse_id', 'product_id'])
            ->firstOrFail();
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
        LabCode::query()->create([
            'code' => '',
            'codeable_type' => 'analysis',
            'cl_month' => now()->format('y/m'),
            'collection_id' => $collectionProduct->id,
        ]);

        $this->actingAs($user)
            ->post(route('programmedcollections.PlaceProductsInAnalysis', $collectionProduct))
            ->assertRedirect();

        Bus::assertDispatched(PlaceProductsInAnalysis::class, fn (PlaceProductsInAnalysis $job): bool => (int) $job->collection_product_id === $collectionProduct->id
        );

        $this->actingAs($user)
            ->post(route('programmedcollections.PlaceProductsInAnalysis', $source))
            ->assertNotFound();
    }

    public function test_result_validation_rejects_cross_sample_lineage(): void
    {
        $user = $this->verifiedAdmin();
        $result = Result::query()
            ->whereNotNull([
                'sample_id', 'product_id', 'parameter_id', 'code_id', 'profile_id', 'matrix_id',
                'collection_id', 'type_id', 'unit_id', 'nwp_id', 'protocol_id', 'standard_id',
            ])
            ->whereHas('sample.analysis')
            ->firstOrFail();
        $otherSample = Sample::query()
            ->whereKeyNot($result->sample_id)
            ->whereHas('analysis')
            ->firstOrFail();

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
        Event::fake([AnalysisResultsApproved::class, AnalysisResultsValidated::class]);

        $user = $this->verifiedAdmin();
        $analysis = Analysis::query()
            ->whereHas('sample.results')
            ->whereHas('sample.collection.collection')
            ->with('sample.results', 'sample.collection.collection')
            ->firstOrFail();
        $result = $analysis->sample->results->firstOrFail();
        $collectionProduct = $analysis->sample->collection->collection;

        Analysis::query()
            ->whereHas('sample', fn ($query) => $query->where('cl_id', $analysis->cl_id))
            ->update(['end_date' => now(), 'status' => true]);
        $analysis->update(['end_date' => null, 'status' => false]);
        QualityCertificate::query()->where('collection_id', $collectionProduct->id)->delete();

        $notifier = Mockery::mock(LaboratoryWorkflowNotifier::class);
        $notifier->shouldReceive('notifyResultsApproved')->once();

        (new ApproveAnalysisResults([
            [
                'result_id' => $result->id,
                'approved_by' => $user->name,
                'approved_by_id' => $user->id,
                'approved_date' => now(),
                'approved_value' => $result->verified_value ?? $result->inserted_value ?? '1',
            ],
        ], $analysis->id, $user))->handle($notifier);

        $collectionProduct->refresh();
        $this->assertTrue($collectionProduct->status);
        $this->assertTrue($collectionProduct->processed);
        $this->assertSame('Concluída', $collectionProduct->sample_status);
        $this->assertNotNull($collectionProduct->analysis_end_date);
        Event::assertDispatchedTimes(AnalysisResultsValidated::class, 1);

        if ($collectionProduct->sampleEntry) {
            $this->assertSame('COMPLETADO', $collectionProduct->sampleEntry->fresh()->status);
        }
    }

    public function test_report_certificate_generation_is_idempotent(): void
    {
        $result = Result::query()
            ->whereHas('code.collection')
            ->with('code.collection')
            ->firstOrFail();
        $collectionProduct = $result->code->collection;
        QualityCertificate::query()->where('collection_id', $collectionProduct->id)->delete();
        $listener = new GenerateAnalysisReportDocument;
        $event = new AnalysisResultsValidated($result, $this->verifiedAdmin()->id);

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
        return Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function collectionProductPayload(CollectionProduct $source, array $overrides = []): array
    {
        return array_merge([
            'product_id' => $source->product_id,
            'temperature_id' => $source->temperature_id,
            'vehicle_id' => $source->vehicle_id,
            'collection_id' => null,
            'pack_id' => $source->pack_id,
            'owner_id' => $source->owner_id,
            'result_id' => $source->result_id,
            'invoice_id' => $source->invoice_id,
            'comercial_brand' => $source->comercial_brand,
            'du_no' => $source->du_no,
            'origin' => $source->origin,
            'location' => $source->location,
            'term_no' => $source->term_no,
            'container_no' => $source->container_no,
            'recollection' => false,
            'obs' => $source->obs,
            'sample_status' => $source->sample_status,
            'sampling_plan_ref' => $source->sampling_plan_ref,
            'customer_submitted_info' => $source->customer_submitted_info,
            'temperature_value' => $source->temperature_value,
            'processed' => false,
            'collected_by_lab' => true,
            'expiry_date' => $this->dateValue($source->expiry_date),
            'production_date' => $this->dateValue($source->production_date),
            'collection_date' => now()->toDateString(),
            'qty' => $source->qty ?: '1',
            'collected_qty' => $source->collected_qty ?: '1',
            'lot' => $source->lot,
            'bl' => $source->bl,
            'invoiced' => false,
            'status' => false,
        ], $overrides);
    }

    private function assertAccessionMetadata(CollectionProduct $record): void
    {
        $this->assertSame('Recebida para controlo', $record->sample_status);
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

    private function dateValue(mixed $value): ?string
    {
        return filled($value) ? substr((string) $value, 0, 10) : null;
    }
}
