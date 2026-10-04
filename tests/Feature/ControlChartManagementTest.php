<?php

namespace Tests\Feature;

use App\Models\ControlChart;
use App\Models\ControlChartPoint;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Support\ControlChartDocument;
use App\Support\ControlChartEvaluation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Control charts as a laboratory uses them: created per test, fed with
 * control values, judged against their limits, acted on, kept as a record.
 */
class ControlChartManagementTest extends TestCase
{
    use DatabaseTransactions;

    private VAPLab $lab;

    private function member(array $permissions = [], bool $admin = false): User
    {
        $user = User::factory()->create(['is_active' => true]);
        if ($admin) {
            $user->assignRole(Role::findOrCreate('admin', 'web'));
        }
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->lab ??= VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $user->id]);
        $this->withSession(['active_lab_id' => $this->lab->id]);

        return $user;
    }

    private function chart(array $attributes = []): ControlChart
    {
        return ControlChart::factory()->create([...$attributes, 'lab_id' => $this->lab->id]);
    }

    /**
     * @param  list<float>  $values
     */
    private function points(ControlChart $chart, array $values): void
    {
        foreach ($values as $index => $value) {
            ControlChartPoint::factory()->create(['control_chart_id' => $chart->id, 'value' => $value, 'measured_at' => now()->subDays(count($values) - $index)]);
        }
    }

    public function test_a_chart_is_created_with_entered_limits_and_opens_on_its_page(): void
    {
        $user = $this->actingAs($this->member(admin: true));

        $this->post(route('control-charts.store'), [
            'name' => 'Chumbo em água · MRC',
            'chart_type' => 'mean',
            'method' => 'ISO 17294-2',
            'control_material' => 'MRC 1643f',
            'unit' => 'µg/L',
            'centre_line' => '18,5',
            'standard_deviation' => '0,6',
            'limits_basis' => 'Valor certificado; s da validação.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $chart = ControlChart::query()->where('name', 'Chumbo em água · MRC')->firstOrFail();
        $this->assertSame($this->lab->id, $chart->lab_id);
        $this->assertSame(18.5, $chart->centre_line);
        $this->assertSame('entered', $chart->limits_source);
        $this->assertNotNull($chart->limits_set_at);
        $this->assertEqualsWithDelta(20.3, $chart->limits()['upper_action'], 1e-9);

        $this->get(route('control-charts.show', $chart))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('ControlCharts/Show')
            ->where('chart.name', 'Chumbo em água · MRC')
            ->where('limits.lower_warning', 17.3)
            ->where('limitHistory.0.centre_line', 18.5)
            ->has('rules', 4));
    }

    public function test_a_mean_chart_needs_both_lines_and_the_type_is_fixed(): void
    {
        $this->actingAs($this->member(admin: true));

        $this->post(route('control-charts.store'), ['name' => 'Sem s', 'chart_type' => 'mean', 'centre_line' => '10', 'limits_basis' => 'x'])
            ->assertSessionHasErrors('standard_deviation');
        $this->post(route('control-charts.store'), ['name' => 'Tipo', 'chart_type' => 'cusum'])->assertSessionHasErrors('chart_type');

        $chart = $this->chart();
        $this->put(route('control-charts.update', $chart), ['name' => 'Outra', 'chart_type' => 'range'])->assertSessionHasErrors('chart_type');
        $this->assertSame('mean', $chart->fresh()->chart_type);
    }

    public function test_recorded_points_are_judged_and_an_out_of_control_point_waits_for_an_action(): void
    {
        $this->actingAs($this->member(admin: true));
        $chart = $this->chart(['centre_line' => 10, 'standard_deviation' => 1]);

        foreach (['10,4', '9,7', '13,6'] as $day => $value) {
            $this->post(route('control-charts.points.store', $chart), ['measured_at' => now()->subDays(3 - $day)->toDateString(), 'value' => $value, 'run_reference' => 'C-'.$day])
                ->assertSessionHasNoErrors();
        }

        $this->get(route('control-charts.show', $chart))->assertInertia(fn (Assert $page) => $page
            ->where('points.0.state', ControlChartEvaluation::IN_CONTROL)
            ->where('points.2.state', ControlChartEvaluation::OUT_OF_CONTROL)
            ->where('points.2.rule_labels.0', ControlChartEvaluation::RULES['action'])
            ->where('points.2.needs_action', true)
            ->where('statistics.open_actions', 1));

        $this->get(route('control-charts.index'))->assertInertia(fn (Assert $page) => $page
            ->component('ControlCharts/Index')
            ->where('charts.0.state', ControlChartEvaluation::OUT_OF_CONTROL)
            ->where('totals.out_of_control', 1)
            ->where('totals.open_actions', 1));

        $point = $chart->points()->get()->last();
        $this->put(route('control-charts.points.update', [$chart, $point]), ['excluded' => false, 'corrective_action' => 'Recalibração do ICP-MS; corrida repetida.'])
            ->assertSessionHasNoErrors();

        $point->refresh();
        $this->assertSame('Recalibração do ICP-MS; corrida repetida.', $point->corrective_action);
        $this->assertNotNull($point->corrective_action_at);
        $this->assertSame(13.6, $point->value);
        $this->get(route('control-charts.show', $chart))->assertInertia(fn (Assert $page) => $page->where('statistics.open_actions', 0));
    }

    public function test_a_range_chart_records_both_duplicates_and_plots_their_difference(): void
    {
        $this->actingAs($this->member(admin: true));
        $chart = $this->chart(['chart_type' => 'range', 'centre_line' => 0.4, 'standard_deviation' => null]);

        $this->post(route('control-charts.points.store', $chart), ['measured_at' => now()->toDateString(), 'replicate_a' => '5,10', 'replicate_b' => '6,50'])
            ->assertSessionHasNoErrors();
        $this->post(route('control-charts.points.store', $chart), ['measured_at' => now()->toDateString(), 'value' => '1'])
            ->assertSessionHasErrors(['value', 'replicate_a', 'replicate_b']);

        $point = $chart->points()->sole();
        $this->assertEqualsWithDelta(1.4, $point->value, 1e-9);
        $this->assertSame(5.1, $point->replicate_a);
        $this->assertSame(ControlChartEvaluation::OUT_OF_CONTROL, (new ControlChartDocument($chart->fresh()))->points()[0]['state']);
    }

    public function test_an_excluded_point_needs_a_reason_and_leaves_the_rules_and_the_computed_limits(): void
    {
        $this->actingAs($this->member(admin: true));
        $chart = $this->chart(ControlChart::factory()->withoutLimits()->raw());
        $this->points($chart, [9, 10, 11, 9, 10, 11, 9, 10, 11, 10, 55]);
        $outlier = $chart->points()->get()->last();

        $this->put(route('control-charts.points.update', [$chart, $outlier]), ['excluded' => true])->assertSessionHasErrors('exclusion_reason');
        $this->put(route('control-charts.points.update', [$chart, $outlier]), ['excluded' => true, 'exclusion_reason' => 'Erro de transcrição.'])
            ->assertSessionHasNoErrors();

        $this->post(route('control-charts.limits', $chart))->assertSessionHasNoErrors();

        $chart->refresh();
        $this->assertSame('computed', $chart->limits_source);
        $this->assertSame(10, $chart->limits_point_count);
        $this->assertEqualsWithDelta(10.0, $chart->centre_line, 1e-9);
        $this->assertEqualsWithDelta(sqrt(6 / 9), $chart->standard_deviation, 1e-6);
        $this->assertStringContainsString('10 pontos incluídos', $chart->limits_basis);
        $this->assertSame(55.0, $outlier->fresh()->value, 'an excluded value stays on record');
    }

    public function test_limits_are_not_computed_from_too_few_points(): void
    {
        $this->actingAs($this->member(admin: true));
        $chart = $this->chart(ControlChart::factory()->withoutLimits()->raw());
        $this->points($chart, [9, 10, 11]);

        $this->post(route('control-charts.limits', $chart))->assertSessionHasErrors('limits');
        $this->assertNull($chart->fresh()->centre_line);
    }

    public function test_archiving_keeps_the_limits_and_stops_new_points(): void
    {
        $this->actingAs($this->member(admin: true));
        $chart = $this->chart();

        $this->put(route('control-charts.update', $chart), ['status' => 'archived'])->assertSessionHasNoErrors();

        $chart->refresh();
        $this->assertSame('archived', $chart->status);
        $this->assertSame(10.0, $chart->centre_line);
        $this->post(route('control-charts.points.store', $chart), ['measured_at' => now()->toDateString(), 'value' => 10])->assertStatus(409);
    }

    public function test_the_pdf_is_a_controlled_record_of_the_chart(): void
    {
        $this->actingAs($this->member(admin: true));
        $chart = $this->chart(['name' => 'Nitratos <água>', 'unit' => 'mg/L']);
        $this->points($chart, [10.2, 9.9, 13.5]);
        $chart->points()->get()->last()->update(['corrective_action' => 'Padrão refeito.']);

        $response = $this->get(route('control-charts.pdf', $chart))->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));

        $document = new ControlChartDocument($chart->fresh()->load('points'));
        $svg = $document->svg();
        $this->assertStringStartsWith('<svg', $svg);
        $this->assertSame(3, substr_count($svg, '<circle'));
        $this->assertStringContainsString('fill="#b42332"', $svg);
        $this->assertStringContainsString('LSA 11,5', $svg);

        $html = view('PDFs.control-chart', ['chart' => $chart->fresh()->load('points'), 'document' => $document])->render();
        $this->assertStringContainsString('Carta de Controlo', $html);
        $this->assertStringContainsString('Nitratos &lt;água&gt;', $html);
        $this->assertStringContainsString('Padrão refeito.', $html);
        $this->assertStringContainsString('data:image/svg+xml;base64,', $html);
        $this->assertStringContainsString('LC ± 2s', $html);
    }

    /**
     * @return array<string, array{string, string, array<string, mixed>}>
     */
    public static function guardedRoutes(): array
    {
        return [
            'list' => ['get', 'control-charts.index', []],
            'create' => ['post', 'control-charts.store', ['name' => 'X', 'chart_type' => 'mean']],
        ];
    }

    #[DataProvider('guardedRoutes')]
    public function test_a_member_without_the_permission_is_refused(string $method, string $route, array $payload): void
    {
        $this->actingAs($this->member());

        $this->{$method}(route($route), $payload)->assertForbidden();
    }

    public function test_a_reader_sees_the_chart_but_cannot_record_on_it(): void
    {
        $this->actingAs($this->member(['view_control_charts']));
        $chart = $this->chart();

        $this->get(route('control-charts.show', $chart))->assertOk()->assertInertia(fn (Assert $page) => $page->where('permissions.edit', false));
        $this->post(route('control-charts.points.store', $chart), ['measured_at' => now()->toDateString(), 'value' => 10])->assertForbidden();
        $this->post(route('control-charts.limits', $chart))->assertForbidden();
    }

    public function test_another_laboratorys_chart_is_not_found(): void
    {
        $this->actingAs($this->member(admin: true));
        $foreign = ControlChart::factory()->create();
        $foreignPoint = ControlChartPoint::factory()->create(['control_chart_id' => $foreign->id]);
        $own = $this->chart();

        $this->get(route('control-charts.show', $foreign))->assertNotFound();
        $this->get(route('control-charts.pdf', $foreign))->assertNotFound();
        $this->post(route('control-charts.points.store', $foreign), ['measured_at' => now()->toDateString(), 'value' => 1])->assertNotFound();
        // A point is reached only through its own chart.
        $this->put(route('control-charts.points.update', [$own, $foreignPoint]), ['excluded' => false])->assertNotFound();
        $this->get(route('control-charts.index'))->assertInertia(fn (Assert $page) => $page->has('charts', 1)->where('charts.0.id', $own->id));
    }
}
