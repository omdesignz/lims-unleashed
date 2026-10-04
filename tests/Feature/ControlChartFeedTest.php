<?php

namespace Tests\Feature;

use App\Events\AnalysisResultsApproved;
use App\Models\CollectionProduct;
use App\Models\ControlChart;
use App\Models\Customer;
use App\Models\LabCode;
use App\Models\Matrix;
use App\Models\Parameter;
use App\Models\Product;
use App\Models\Result;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Services\ControlChartFeed;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Quality control samples: samples of a product marked as control material.
 * Their approved results reach the control charts of that material on their
 * own, once each.
 */
class ControlChartFeedTest extends TestCase
{
    use DatabaseTransactions;

    private VAPLab $lab;

    private Product $material;

    private Parameter $parameter;

    protected function setUp(): void
    {
        parent::setUp();
        // The approval event also broadcasts; the test exercises its listeners only.
        config(['broadcasting.default' => 'null']);
        $this->lab = VAPLab::factory()->create();
        $matrix = Matrix::query()->create(['description' => 'Água '.Str::uuid()]);
        $this->material = Product::query()->create(['name' => 'MRC 1643f '.Str::uuid(), 'matrix_id' => $matrix->id, 'is_control_material' => true]);
        $this->parameter = Parameter::query()->create(['name' => 'Chumbo '.Str::uuid(), 'code' => 'PB-'.Str::random(6), 'active' => true]);
    }

    private function chart(array $attributes = []): ControlChart
    {
        return ControlChart::factory()->create([
            'lab_id' => $this->lab->id,
            'parameter_id' => $this->parameter->id,
            'control_product_id' => $this->material->id,
            'centre_line' => 18.5,
            'standard_deviation' => 0.4,
            ...$attributes,
        ]);
    }

    /** A sample of a product received in a laboratory, with its lab code. */
    private function sample(?Product $product = null, ?VAPLab $lab = null): LabCode
    {
        $customer = Customer::query()->create(['name' => 'Laboratório '.Str::uuid()]);
        $collection = new CollectionProduct(['customer_id' => $customer->id, 'product_id' => ($product ?? $this->material)->id]);
        $collection->saveQuietly();
        VAPSampleEntry::factory()->createQuietly(['lab_id' => ($lab ?? $this->lab)->id, 'customer_id' => $customer->id, 'collection_product_id' => $collection->id]);
        $code = new LabCode(['collection_id' => $collection->id, 'code' => 'CQ-'.Str::random(8), 'cl_month' => now()->format('Y')]);
        $code->saveQuietly();

        return $code;
    }

    private function approvedResult(LabCode $code, ?string $approvedValue, array $attributes = []): Result
    {
        $result = new Result;
        $result->forceFill([
            'code_id' => $code->id,
            'collection_id' => $code->collection_id,
            'parameter_id' => $this->parameter->id,
            'approved_value' => $approvedValue,
            'approved_date' => $approvedValue === null ? null : now()->subHour(),
            'inserted_date' => now()->subDays(1),
            ...$attributes,
        ])->saveQuietly();

        return $result;
    }

    public function test_approved_results_of_control_samples_become_points_once(): void
    {
        $chart = $this->chart();
        $first = $this->approvedResult($this->sample(), '18,6');
        $this->approvedResult($this->sample(), '18.9', ['approved_date' => now()]);
        $this->approvedResult($this->sample(), null);                                     // not approved yet
        $this->approvedResult($this->sample(), 'Não detectado');                          // not a number
        $this->approvedResult($this->sample(), '30', ['parameter_id' => Parameter::query()->create(['name' => 'Outro', 'code' => 'X-'.Str::random(5)])->id]);
        $this->approvedResult($this->sample(Product::query()->create(['name' => 'Água de cliente', 'matrix_id' => $this->material->matrix_id])), '25');
        $this->approvedResult($this->sample(lab: VAPLab::factory()->create()), '26');   // another laboratory

        $this->assertSame(2, app(ControlChartFeed::class)->feed($chart));
        $this->assertSame(0, app(ControlChartFeed::class)->feed($chart), 'a result is plotted once');

        $points = $chart->points()->get();
        $this->assertSame([18.6, 18.9], $points->pluck('value')->all());
        $this->assertSame($first->id, $points->first()->result_id);
        $this->assertNotNull($points->first()->sample_entry_id);
        $this->assertStringStartsWith('CQ-', $points->first()->run_reference);
    }

    public function test_an_excluded_point_is_not_brought_back(): void
    {
        $chart = $this->chart();
        $this->approvedResult($this->sample(), '30');
        app(ControlChartFeed::class)->feed($chart);
        $chart->points()->update(['excluded' => true, 'exclusion_reason' => 'Contaminação da amostra de controlo.']);

        $this->assertSame(0, app(ControlChartFeed::class)->feed($chart));
        $this->assertSame(1, $chart->points()->count());
    }

    public function test_a_range_chart_takes_the_first_two_approved_results_of_a_sample(): void
    {
        $chart = $this->chart(['chart_type' => 'range', 'centre_line' => 0.4, 'standard_deviation' => null]);
        $paired = $this->sample();
        $analysis = $this->approvedResult($paired, '5,1', ['approved_date' => now()->subHours(3)]);
        $counter = $this->approvedResult($paired, '5,6', ['approved_date' => now()->subHours(2)]);
        $this->approvedResult($this->sample(), '7');                                      // no duplicate yet

        $this->assertSame(1, app(ControlChartFeed::class)->feed($chart));

        $point = $chart->points()->sole();
        $this->assertEqualsWithDelta(0.5, $point->value, 1e-9);
        $this->assertSame([5.1, 5.6], [$point->replicate_a, $point->replicate_b]);
        $this->assertSame([$analysis->id, $counter->id], [$point->result_id, $point->paired_result_id]);

        $this->approvedResult($paired, '9', ['approved_date' => now()]);               // a third result does not make a new pair
        $this->assertSame(0, app(ControlChartFeed::class)->feed($chart));
    }

    public function test_archived_charts_and_charts_without_a_material_are_not_fed(): void
    {
        $archived = $this->chart(['status' => 'archived']);
        $manual = $this->chart(['control_product_id' => null]);
        $this->approvedResult($this->sample(), '18,5');

        $this->assertSame(0, app(ControlChartFeed::class)->feed($archived));
        $this->assertSame(0, app(ControlChartFeed::class)->feed($manual));
    }

    public function test_approving_a_control_sample_feeds_its_charts(): void
    {
        $chart = $this->chart();
        $code = $this->sample();
        $this->approvedResult($code, '18,7');

        event(new AnalysisResultsApproved(User::factory()->create(), $code));

        $this->assertSame(18.7, $chart->points()->sole()->value);
    }

    public function test_approving_a_customer_sample_feeds_nothing(): void
    {
        $chart = $this->chart();
        $code = $this->sample(Product::query()->create(['name' => 'Água de cliente', 'matrix_id' => $this->material->matrix_id]));
        $this->approvedResult($code, '18,7');

        event(new AnalysisResultsApproved(User::factory()->create(), $code));

        $this->assertSame(0, $chart->points()->count());
    }

    public function test_a_chart_tied_to_a_control_material_brings_its_results_when_created(): void
    {
        $this->actingAsLabAdmin();
        $this->approvedResult($this->sample(), '18,4');
        $this->approvedResult($this->sample(), '18,8');

        $this->post(route('control-charts.store'), [
            'name' => 'Chumbo · MRC',
            'chart_type' => 'mean',
            'parameter_id' => ['value' => $this->parameter->id, 'label' => $this->parameter->name],
            'control_product_id' => ['value' => $this->material->id, 'label' => $this->material->name],
        ])->assertSessionHasNoErrors();

        $chart = ControlChart::query()->where('name', 'Chumbo · MRC')->firstOrFail();
        $this->assertSame(2, $chart->points()->count());

        $this->get(route('control-charts.show', $chart))->assertInertia(fn (Assert $page) => $page
            ->where('chart.feeds_from_results', true)
            ->where('points.0.from_result', true)
            ->has('controlSamples', 2)
            ->where('controlSamples.0.points', 1));

        $this->approvedResult($this->sample(), '18,6');
        $this->post(route('control-charts.feed', $chart))->assertSessionHasNoErrors();
        $this->assertSame(3, $chart->points()->count());
    }

    public function test_only_a_product_marked_as_control_material_can_feed_a_chart(): void
    {
        $this->actingAsLabAdmin();
        $customerProduct = Product::query()->create(['name' => 'Água de cliente', 'matrix_id' => $this->material->matrix_id]);

        $this->post(route('control-charts.store'), ['name' => 'X', 'chart_type' => 'mean', 'control_product_id' => $customerProduct->id])
            ->assertSessionHasErrors('control_product_id');

        $this->getJson(route('control-charts.materials', ['q' => 'MRC 1643f']))
            ->assertOk()
            ->assertJsonFragment(['id' => $this->material->id])
            ->assertJsonMissing(['id' => $customerProduct->id]);
    }

    public function test_the_sample_page_marks_a_quality_control_sample_and_names_its_charts(): void
    {
        $this->actingAsLabAdmin();
        $chart = $this->chart(['name' => 'Chumbo · MRC 1643f']);
        $code = $this->sample();
        $entry = VAPSampleEntry::query()->where('collection_product_id', $code->collection_id)->sole();
        $customerCode = $this->sample(Product::query()->create(['name' => 'Água de cliente', 'matrix_id' => $this->material->matrix_id]));
        $customerEntry = VAPSampleEntry::query()->where('collection_product_id', $customerCode->collection_id)->sole();

        $this->get(route('vap_samples.show', $entry))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('qualityControl.material', $this->material->name)
            ->where('qualityControl.charts.0.name', 'Chumbo · MRC 1643f')
            ->where('qualityControl.charts.0.url', route('control-charts.show', $chart)));
        $this->get(route('vap_samples.show', $customerEntry))->assertOk()->assertInertia(fn (Assert $page) => $page->where('qualityControl', null));
    }

    private function actingAsLabAdmin(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $user->id]);
        $this->actingAs($user)->withSession(['active_lab_id' => $this->lab->id]);
    }
}
