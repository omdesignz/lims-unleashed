<?php

namespace Tests\Feature;

use App\Metrics\ChartDatasets;
use App\Models\CustomChart;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CustomChartBoardTest extends TestCase
{
    use DatabaseTransactions;

    private VAPLab $lab;

    private User $analyst;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lab = VAPLab::factory()->create();
        $this->analyst = $this->member($this->lab, ['view_samples']);
    }

    /** @param list<string> $permissions */
    private function member(VAPLab $lab, array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        DB::table('lab_user')->insert(['user_id' => $user->id, 'lab_id' => $lab->id]);

        return $user;
    }

    /** @return array<string, string|null> */
    private function definition(array $overrides = []): array
    {
        return [
            'title' => 'Amostras por estado',
            'dataset' => 'samples',
            'measure' => 'count',
            'dimension' => 'status',
            'split' => null,
            'kind' => 'column',
            'period' => '30d',
            ...$overrides,
        ];
    }

    public function test_guests_cannot_open_the_board(): void
    {
        $this->get(route('analytics.board'))->assertRedirect();
    }

    public function test_the_board_offers_only_datasets_the_person_may_read(): void
    {
        $this->actingAs($this->analyst)->get(route('analytics.board'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Analytics/Board')
                ->has('datasets', 1)
                ->where('datasets.0.key', 'samples')
                ->where('charts', [])
                ->has('periods', 4));
    }

    public function test_preview_counts_only_the_active_laboratory_and_labels_codes(): void
    {
        VAPSampleEntry::factory()->count(3)->create(['lab_id' => $this->lab->id, 'status' => 'POR_INICIAR', 'received_at' => now()->subDays(2)]);
        VAPSampleEntry::factory()->create(['lab_id' => $this->lab->id, 'status' => 'COMPLETADO', 'received_at' => now()->subDays(3)]);
        VAPSampleEntry::factory()->create(['lab_id' => $this->lab->id, 'status' => 'COMPLETADO', 'received_at' => now()->subDays(60)]);
        VAPSampleEntry::factory()->count(5)->create(['status' => 'POR_INICIAR', 'received_at' => now()->subDay()]);

        $this->actingAs($this->analyst)->getJson(route('analytics.charts.preview', $this->definition()))
            ->assertOk()
            ->assertJson([
                'categories' => ['Por iniciar', 'Concluída'],
                'series' => [['name' => 'Amostras', 'data' => [3, 1]]],
                'format' => 'count',
                'dimension_type' => 'category',
            ]);
    }

    public function test_time_groupings_show_every_step_of_the_period_including_empty_ones(): void
    {
        VAPSampleEntry::factory()->create(['lab_id' => $this->lab->id, 'received_at' => now()->startOfDay()->addHour()]);

        $response = $this->actingAs($this->analyst)->getJson(route('analytics.charts.preview', $this->definition(['dimension' => 'day', 'kind' => 'line'])))
            ->assertOk()
            ->assertJsonPath('dimension_type', 'time');

        $this->assertCount(30, $response->json('categories'));
        $this->assertSame(1, array_sum($response->json('series.0.data')));
        $this->assertSame(1, $response->json('series.0.data.29'));
    }

    public function test_a_breakdown_draws_one_series_per_value(): void
    {
        VAPSampleEntry::factory()->count(2)->create(['lab_id' => $this->lab->id, 'status' => 'POR_INICIAR', 'sample_type' => 'AGUA', 'received_at' => now()]);
        VAPSampleEntry::factory()->create(['lab_id' => $this->lab->id, 'status' => 'POR_INICIAR', 'sample_type' => 'ROTINA', 'received_at' => now()]);

        $this->actingAs($this->analyst)->getJson(route('analytics.charts.preview', $this->definition(['split' => 'sample_type'])))
            ->assertOk()
            ->assertJsonPath('categories', ['Por iniciar'])
            ->assertJsonPath('series.0.name', 'Água')
            ->assertJsonPath('series.0.data', [2])
            ->assertJsonPath('series.1.name', 'Rotina')
            ->assertJsonPath('series.1.data', [1]);
    }

    public function test_categories_past_the_limit_fold_into_outros(): void
    {
        foreach (range(1, 14) as $index) {
            VAPSampleEntry::factory()->count($index)->create(['lab_id' => $this->lab->id, 'sample_type' => "TIPO_{$index}", 'received_at' => now()]);
        }

        $response = $this->actingAs($this->analyst)->getJson(route('analytics.charts.preview', $this->definition(['dimension' => 'sample_type', 'kind' => 'bar'])))->assertOk();

        $this->assertCount(13, $response->json('categories'));
        $this->assertSame('TIPO_14', $response->json('categories.0'));
        $this->assertSame('Outros', $response->json('categories.12'));
        $this->assertSame(1 + 2, $response->json('series.0.data.12'));
        $this->assertSame(array_sum(range(1, 14)), array_sum($response->json('series.0.data')));
    }

    public function test_datasets_without_permission_and_unfit_combinations_are_refused(): void
    {
        $this->actingAs($this->analyst);

        $this->getJson(route('analytics.charts.preview', $this->definition(['dataset' => 'invoices', 'measure' => 'count'])))
            ->assertUnprocessable()->assertJsonValidationErrors('dataset');
        $this->getJson(route('analytics.charts.preview', $this->definition(['kind' => 'line'])))
            ->assertUnprocessable()->assertJsonValidationErrors('kind');
        $this->getJson(route('analytics.charts.preview', $this->definition(['dimension' => 'month', 'kind' => 'donut'])))
            ->assertUnprocessable()->assertJsonValidationErrors('kind');
        $this->getJson(route('analytics.charts.preview', $this->definition(['split' => 'status'])))
            ->assertUnprocessable()->assertJsonValidationErrors('split');
        $this->getJson(route('analytics.charts.preview', $this->definition(['measure' => 'turnaround', 'split' => 'sample_type'])))
            ->assertUnprocessable()->assertJsonValidationErrors('split');
        $this->getJson(route('analytics.charts.preview', $this->definition(['dimension' => 'sql_injection'])))
            ->assertUnprocessable()->assertJsonValidationErrors('dimension');
        $this->getJson(route('analytics.charts.preview', $this->definition(['period' => '5y'])))
            ->assertUnprocessable()->assertJsonValidationErrors('period');
    }

    public function test_reagent_quantities_only_add_up_per_reagent(): void
    {
        $inventoryUser = $this->member($this->lab, ['view_inventory', 'view_iitems']);
        $this->assertContains('consumption', collect(app(ChartDatasets::class)->available($inventoryUser))->pluck('key')->all());
        $this->assertNotContains('consumption', collect(app(ChartDatasets::class)->available($this->member($this->lab, ['view_inventory'])))->pluck('key')->all());

        $this->actingAs($inventoryUser)
            ->getJson(route('analytics.charts.preview', $this->definition(['dataset' => 'consumption', 'measure' => 'quantity', 'dimension' => 'reagent', 'kind' => 'bar'])))
            ->assertOk()->assertJsonPath('format', 'decimal');
        $this->actingAs($inventoryUser)
            ->getJson(route('analytics.charts.preview', $this->definition(['dataset' => 'consumption', 'measure' => 'quantity', 'dimension' => 'month'])))
            ->assertUnprocessable()->assertJsonValidationErrors('measure');
    }

    public function test_saved_charts_belong_to_their_owner_and_laboratory_with_chosen_colours(): void
    {
        $this->actingAs($this->analyst)->post(route('analytics.charts.store'), $this->definition(['colors' => ['Amostras' => '#4a3aa7']]))
            ->assertRedirect()->assertSessionHas('success');
        $this->post(route('analytics.charts.store'), $this->definition(['title' => 'Segundo', 'kind' => 'bar']))->assertRedirect();

        $charts = CustomChart::query()->where('user_id', $this->analyst->id)->orderBy('position')->get();
        $this->assertCount(2, $charts);
        $this->assertSame($this->lab->id, $charts[0]->lab_id);
        $this->assertSame(['Amostras' => '#4a3aa7'], $charts[0]->colors);
        $this->assertSame([1, 2], $charts->pluck('position')->all());

        $this->get(route('analytics.board'))->assertInertia(fn (Assert $page) => $page
            ->has('charts', 2)
            ->where('charts.0.title', 'Amostras por estado')
            ->where('charts.0.colors.Amostras', '#4a3aa7')
            ->where('charts.0.unavailable', null)
            ->has('charts.0.data.categories'));
    }

    public function test_saving_requires_a_title_and_hexadecimal_colours(): void
    {
        $this->actingAs($this->analyst)
            ->post(route('analytics.charts.store'), $this->definition(['title' => '', 'colors' => ['Amostras' => 'red']]))
            ->assertSessionHasErrors(['title', 'colors.Amostras']);

        $this->assertDatabaseCount('custom_charts', 0);
    }

    public function test_charts_of_other_people_or_laboratories_cannot_be_changed(): void
    {
        $colleague = $this->member($this->lab, ['view_samples']);
        $theirs = CustomChart::factory()->create(['user_id' => $colleague->id, 'lab_id' => $this->lab->id]);
        $otherLab = CustomChart::factory()->create(['user_id' => $this->analyst->id]);

        $this->actingAs($this->analyst);
        $this->put(route('analytics.charts.update', $theirs->id), $this->definition(['title' => 'Tomado']))->assertNotFound();
        $this->delete(route('analytics.charts.destroy', $theirs->id))->assertNotFound();
        $this->delete(route('analytics.charts.destroy', $otherLab->id))->assertNotFound();

        $this->assertDatabaseHas('custom_charts', ['id' => $theirs->id, 'title' => $theirs->title]);
        $this->assertDatabaseHas('custom_charts', ['id' => $otherLab->id]);
    }

    public function test_owner_can_edit_reorder_and_remove_charts(): void
    {
        $first = CustomChart::factory()->create(['user_id' => $this->analyst->id, 'lab_id' => $this->lab->id, 'position' => 0]);
        $second = CustomChart::factory()->create(['user_id' => $this->analyst->id, 'lab_id' => $this->lab->id, 'position' => 1]);

        $this->actingAs($this->analyst);
        $this->put(route('analytics.charts.update', $first->id), $this->definition(['title' => 'Renomeado', 'kind' => 'donut']))->assertRedirect();
        $this->assertSame(['Renomeado', 'donut'], [$first->fresh()->title, $first->fresh()->kind]);

        $this->patch(route('analytics.charts.reorder'), ['order' => [$first->id]])->assertSessionHasErrors('order');
        $this->patch(route('analytics.charts.reorder'), ['order' => [$second->id, $first->id]])->assertRedirect();
        $this->assertSame([1, 0], [$first->fresh()->position, $second->fresh()->position]);

        $this->delete(route('analytics.charts.destroy', $second->id))->assertRedirect();
        $this->assertDatabaseMissing('custom_charts', ['id' => $second->id]);
    }

    public function test_a_chart_whose_data_is_no_longer_readable_is_shown_without_values(): void
    {
        CustomChart::factory()->create(['user_id' => $this->analyst->id, 'lab_id' => $this->lab->id, 'dataset' => 'invoices', 'measure' => 'count', 'dimension' => 'month']);

        $this->actingAs($this->analyst)->get(route('analytics.board'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('charts.0.data', null)
            ->whereType('charts.0.unavailable', 'string'));
    }

    public function test_every_dataset_measure_and_grouping_runs_against_the_schema(): void
    {
        $everyone = $this->member($this->lab, ['view_samples', 'view_occurrences', 'view_maintenance_tasks', 'view_inventory', 'view_iitems', 'view_iequipments', 'view_itransactions', 'view_invoices', 'view_proposals']);
        $registry = app(ChartDatasets::class);
        $datasets = $registry->available($everyone);
        $this->assertSame(['samples', 'nonconformities', 'occurrences', 'maintenance', 'consumption', 'movements', 'invoices', 'proposals'], array_column($datasets, 'key'));

        foreach ($datasets as $dataset) {
            $categories = array_values(array_filter($dataset['dimensions'], fn (array $dimension): bool => $dimension['type'] === 'category'));
            foreach ($dataset['measures'] as $measure) {
                foreach ($dataset['dimensions'] as $dimension) {
                    $definition = ['dataset' => $dataset['key'], 'measure' => $measure['key'], 'dimension' => $dimension['key'], 'split' => null, 'kind' => 'column', 'period' => 'all'];
                    if ($registry->problems($everyone, $definition) !== []) {
                        continue;
                    }
                    $chart = $registry->run($everyone, $this->lab->id, $definition);
                    $this->assertSame($measure['format'], $chart['format'], "{$dataset['key']}.{$measure['key']} by {$dimension['key']}");

                    $split = collect($categories)->first(fn (array $candidate): bool => $candidate['key'] !== $dimension['key']);
                    if ($split !== null && $measure['additive']) {
                        $registry->run($everyone, $this->lab->id, [...$definition, 'split' => $split['key']]);
                    }
                }
            }
        }
    }
}
