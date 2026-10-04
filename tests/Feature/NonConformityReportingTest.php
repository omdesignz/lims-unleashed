<?php

namespace Tests\Feature;

use App\Exports\NonConformitiesExport;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPNonConformity;
use App\Models\VAPNonConformityAction;
use App\Support\NonConformityQuery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PDF;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class NonConformityReportingTest extends TestCase
{
    use DatabaseTransactions;

    private VAPLab $lab;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-04 15:00:00'));
        $this->lab = VAPLab::factory()->create(['name' => 'Quality report lab']);
        $this->operator = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $this->operator->id]);
        $this->operator->givePermissionTo(Permission::findOrCreate('view_occurrences', 'web'));
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
    }

    private function record(array $attributes = []): VAPNonConformity
    {
        return VAPNonConformity::create(array_replace([
            'lab_id' => $this->lab->id, 'nc_number' => fake()->unique()->bothify('NC-REPORT-########'),
            'title' => 'Quality record', 'description' => 'Observed evidence', 'reported_by' => 'Reporter',
            'reported_at' => '2026-10-04 12:00:00', 'status' => 'opened', 'severity' => 'medium', 'category' => 'quality',
        ], $attributes));
    }

    public function test_list_summaries_charts_and_exports_use_the_same_filtered_archive(): void
    {
        $match = $this->record(['title' => 'Needle 100%_match', 'severity' => 'critical', 'due_date' => now()->subMinute()]);
        $match->delete();
        $this->record(['title' => 'Needle 100%_match', 'severity' => 'critical']);
        $this->record(['title' => 'Needle 100XXmatch', 'severity' => 'critical'])->delete();
        $this->record(['title' => 'Needle 100%_match', 'severity' => 'critical', 'reported_at' => '2026-10-03 23:59:59'])->delete();
        $this->record(['title' => 'Needle 100%_match', 'severity' => 'critical', 'lab_id' => VAPLab::factory()->create()->id])->delete();
        $filters = ['archived' => 1, 'search' => 'needle 100%_', 'severity' => 'critical', 'status' => 'opened',
            'category' => 'quality', 'start_date' => '2026-10-04', 'end_date' => '2026-10-04'];
        $this->get(route('vap_non_conformities.index', $filters))->assertInertia(fn (Assert $page) => $page
            ->has('nonConformities.data', 1)->where('nonConformities.data.0.id', $match->id)
            ->where('stats.total', 1)->where('stats.open', 1)->where('stats.critical', 1)->where('stats.overdue', 1)
            ->where('stats.attention', 1)->where('charts.severity.series', [0, 0, 0, 1]));
        $this->assertSame([$match->id], (new NonConformitiesExport($this->lab->id, $filters))->collection()->modelKeys());

        PDF::shouldReceive('loadView')->once()->withArgs(function (string $view, array $data, array $merge, array $config) use ($match, $filters): bool {
            $this->assertSame('exports.non-conformities.pdf', $view);
            $this->assertSame([$match->id], $data['nonConformities']->modelKeys());
            $this->assertEquals($filters, $data['filters']);
            $this->assertSame($this->lab->name, $data['labName']);
            $this->assertSame('A4-L', $config['format']);

            return true;
        })->andReturn(new class
        {
            public function output(): string
            {
                return '%PDF-verified';
            }
        });
        $this->get(route('vap_non_conformities.export.pdf', $filters))->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')->assertSee('%PDF-verified', false);
    }

    public function test_totals_cover_all_pages_and_attention_does_not_double_count(): void
    {
        for ($i = 0; $i < 21; $i++) {
            $this->record(['severity' => 'critical', 'due_date' => now()->subDay()]);
        }
        $this->record(['status' => 'resolved', 'severity' => 'low', 'due_date' => now()->subDay()]);
        $this->get(route('vap_non_conformities.index', ['page' => 2]))->assertInertia(fn (Assert $page) => $page
            ->has('nonConformities.data', 2)->where('stats.total', 22)->where('stats.overdue', 21)->where('stats.attention', 21)
            ->has('charts.trend.series', 1)->where('charts.trend.series.0.name', 'Reportadas'));
    }

    public function test_invalid_filters_are_rejected_by_list_and_both_exports(): void
    {
        foreach (['index', 'export.pdf', 'export.excel'] as $route) {
            foreach ([
                ['search' => ['nested']], ['status' => 'made-up'], ['severity' => 'maximum'],
                ['category' => 'unknown'], ['archived' => 'sometimes'], ['start_date' => '2026-02-30'],
                ['start_date' => '2026-10-04', 'end_date' => '2026-10-03'],
            ] as $filters) {
                $this->getJson(route('vap_non_conformities.'.$route, $filters))->assertUnprocessable();
            }
        }
    }

    public function test_xlsx_history_preserves_archived_actions_zero_null_and_formula_text(): void
    {
        $record = $this->record(['title' => '=1+1', 'nc_number' => 'NC/UNSAFE-PATH', 'corrective_actions' => '0']);
        $action = VAPNonConformityAction::create(['lab_id' => $this->lab->id, 'nc_id' => $record->id,
            'correction' => '0', 'corrective_action' => '=HYPERLINK("https://example.invalid")', 'evidence' => '@evidence', 'was_effective' => null]);
        $action->delete();
        VAPNonConformityAction::create(['lab_id' => $this->lab->id, 'nc_id' => $record->id,
            'correction' => 'Second action', 'was_effective' => false]);
        $record->delete();
        $response = $this->get(route('vap_non_conformities.export.details.excel', $record))->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringNotContainsString('UNSAFE-PATH', $response->headers->get('Content-Disposition'));
        $sheet = IOFactory::load($response->baseResponse->getFile()->getPathname());
        try {
            $details = $sheet->getSheetByName('Dossier');
            $this->assertSame('=1+1', $details->getCell('B3')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $details->getCell('B3')->getDataType());
            $this->assertSame('0', $details->getCell('B20')->getValue());
            $actions = $sheet->getSheetByName('Histórico CAPA');
            $this->assertSame(3, $actions->getHighestRow());
            $this->assertSame('0', $actions->getCell('B2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $actions->getCell('C2')->getDataType());
            $this->assertSame('Não avaliada', $actions->getCell('F2')->getValue());
            $this->assertSame('@evidence', $actions->getCell('G2')->getValue());
            $this->assertSame('Arquivada', $actions->getCell('J2')->getValue());
            $this->assertSame('Second action', $actions->getCell('B3')->getValue());
            $this->assertSame('Não', $actions->getCell('F3')->getValue());
            $this->assertSame('Activa', $actions->getCell('J3')->getValue());
        } finally {
            $sheet->disconnectWorksheets();
        }
        $list = $this->get(route('vap_non_conformities.export.excel', ['archived' => 1]))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $sheet = IOFactory::load($list->baseResponse->getFile()->getPathname());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getActiveSheet()->getCell('B2')->getDataType());
        $this->assertSame('=1+1', $sheet->getActiveSheet()->getCell('B2')->getValue());
        $sheet->disconnectWorksheets();
    }

    public function test_archived_pdf_details_keep_corrective_history_and_narrative_evidence(): void
    {
        $record = $this->record(['corrective_actions' => 'Global corrective evidence', 'root_cause' => '0']);
        $action = VAPNonConformityAction::create(['lab_id' => $this->lab->id, 'nc_id' => $record->id,
            'correction' => '0', 'evidence' => '<script>Evidence text</script>', 'was_effective' => null]);
        $action->delete();
        $record->delete();
        PDF::shouldReceive('loadView')->once()->withArgs(function (string $view, array $data) use ($action): bool {
            $this->assertSame([$action->id], $data['nonConformity']->actions->modelKeys());
            $html = view($view, $data)->render();
            $this->assertStringContainsString('>0</td>', $html);
            foreach (['Global corrective evidence', 'Histórico preservado', 'Não avaliada', '&lt;script&gt;Evidence text&lt;/script&gt;'] as $text) {
                $this->assertStringContainsString($text, $html);
            }

            return true;
        })->andReturn(new class
        {
            public function output(): string
            {
                return '%PDF-verified';
            }
        });
        $this->get(route('vap_non_conformities.export.details.pdf', $record))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_exports_require_current_view_permission_and_lab_membership(): void
    {
        $local = $this->record();
        $peer = $this->record(['lab_id' => VAPLab::factory()->create()->id]);
        $peer->delete();
        foreach (['pdf', 'excel'] as $format) {
            $this->get(route('vap_non_conformities.export.details.'.$format, $peer))->assertNotFound();
        }
        $this->operator->revokePermissionTo('view_occurrences');
        foreach (['pdf', 'excel'] as $format) {
            $this->get(route('vap_non_conformities.export.'.$format))->assertForbidden();
            $this->get(route('vap_non_conformities.export.details.'.$format, $local))->assertForbidden();
        }
    }

    public function test_reopened_lifecycle_exports_keep_ordered_evidence_without_internal_snapshots(): void
    {
        config(['app.timezone' => 'Africa/Luanda']);
        $record = $this->record(['status' => 'in_progress']);
        $record->forceFill(['workflow_revision' => 3, 'workflow_history' => [
            ['revision' => 3, 'action' => 'reopen', 'from' => 'closed', 'to' => 'in_progress', 'actor_name' => 'Reviewer',
                'at' => '2026-10-04T14:00:00+01:00', 'evidence' => '<script>New finding</script>', 'request_id' => 'PRIVATE-REPLAY-ID'],
            ['revision' => 1, 'action' => 'resolve', 'from' => 'opened', 'to' => 'resolved', 'actor_name' => '=Reviewer',
                'at' => '2026-10-04T12:00:00+01:00', 'evidence' => '0', 'snapshot' => ['path' => 'PRIVATE-SNAPSHOT-PATH']],
            ['revision' => 2, 'action' => 'verify', 'from' => 'resolved', 'to' => 'resolved', 'actor_name' => 'Reviewer',
                'at' => '2026-10-04T13:00:00+01:00', 'evidence' => '=HYPERLINK("https://example.invalid")'],
        ]])->save();
        $record->delete();
        $response = $this->get(route('vap_non_conformities.export.details.excel', $record))->assertOk();
        $workbook = IOFactory::load($response->baseResponse->getFile()->getPathname());
        try {
            $history = $workbook->getSheetByName('Histórico do fluxo');
            $this->assertNotNull($history);
            $this->assertSame(4, $history->getHighestRow());
            $this->assertSame('Resolução', $history->getCell('B2')->getValue());
            $this->assertSame('0', $history->getCell('G2')->getValue());
            $this->assertSame('04/10/2026 12:00:00', $history->getCell('F2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $history->getCell('E2')->getDataType());
            $this->assertSame(DataType::TYPE_STRING, $history->getCell('G3')->getDataType());
            $this->assertSame('Reabertura', $history->getCell('B4')->getValue());
            $this->assertSame('Em curso', $history->getCell('D4')->getValue());
            $allValues = json_encode($history->toArray());
            $this->assertStringNotContainsString('PRIVATE-', $allValues);
            $details = collect($workbook->getSheetByName('Dossier')->toArray())->mapWithKeys(fn ($row) => [$row[0] => $row[1]]);
            $this->assertSame('3', $details['Revisão do fluxo']);
            $this->assertSame('Não registada', $details['Resolvida em']);
            $this->assertSame('Não registada', $details['Encerrada em']);
        } finally {
            $workbook->disconnectWorksheets();
        }
        PDF::shouldReceive('loadView')->once()->withArgs(function (string $view, array $data): bool {
            $html = view($view, $data)->render();
            foreach (['Ciclo actual', 'Histórico do fluxo', 'Revisão 1 - Resolução', 'Revisão 3 - Reabertura', '04/10/2026 12:00:00', '>0</td>', '&lt;script&gt;New finding&lt;/script&gt;', 'Não registada'] as $value) {
                $this->assertStringContainsString($value, $html);
            }
            $this->assertStringNotContainsString('PRIVATE-', $html);
            $this->assertStringNotContainsString('<script>', $html);
            // Every page carries the document number and the page of the total.
            $this->assertStringContainsString('footer: doc-footer;', $html);
            $this->assertStringContainsString('Página {PAGENO} de {nbpg}', $html);
            $this->assertStringContainsString('<tr class="lifecycle-entry">', $html);
            $this->assertLessThan(strpos($html, 'Revisão 3 - Reabertura'), strpos($html, 'Revisão 1 - Resolução'));

            return true;
        })->andReturn(new class
        {
            public function output(): string
            {
                return '%PDF-lifecycle';
            }
        });
        $this->get(route('vap_non_conformities.export.details.pdf', $record))->assertOk();
    }

    public function test_current_cycle_evidence_is_reported_and_empty_history_does_not_invent_approvals(): void
    {
        $record = $this->record(['status' => 'closed', 'resolved_at' => '2026-10-04 10:00:00', 'verified_at' => '2026-10-04 11:00:00']);
        $record->forceFill(['resolution_evidence' => '0', 'verification_evidence' => 'Reviewed evidence', 'closed_at' => '2026-10-04 12:00:00'])->save();
        $response = $this->get(route('vap_non_conformities.export.details.excel', $record))->assertOk();
        $workbook = IOFactory::load($response->baseResponse->getFile()->getPathname());
        try {
            $rows = collect($workbook->getSheetByName('Dossier')->toArray())->mapWithKeys(fn ($row) => [$row[0] => $row[1]]);
            $this->assertSame('0', $rows['Evidência de resolução']);
            $this->assertSame('Reviewed evidence', $rows['Evidência de verificação']);
            $this->assertSame('04/10/2026 12:00:00', $rows['Encerrada em']);
            $this->assertSame(1, $workbook->getSheetByName('Histórico do fluxo')->getHighestRow());
        } finally {
            $workbook->disconnectWorksheets();
        }
        PDF::shouldReceive('loadView')->once()->withArgs(function (string $view, array $data): bool {
            $html = view($view, $data)->render();
            $this->assertStringContainsString('Não existem etapas registadas neste fluxo.', $html);
            $this->assertStringContainsString('Reviewed evidence', $html);
            $this->assertStringContainsString('04/10/2026 12:00:00', $html);

            return true;
        })->andReturn(new class
        {
            public function output(): string
            {
                return '%PDF-lifecycle';
            }
        });
        $this->get(route('vap_non_conformities.export.details.pdf', $record))->assertOk();
    }

    public function test_export_limit_rejects_instead_of_silently_truncating(): void
    {
        $template = $this->record()->getAttributes();
        unset($template['id']);
        $rows = [];
        for ($i = 0; $i < NonConformityQuery::EXPORT_LIMIT; $i++) {
            $rows[] = [...$template, 'nc_number' => 'NC-BULK-'.$i];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('v_non_conformities')->insert($chunk);
        }
        foreach (['pdf', 'excel'] as $format) {
            $this->getJson(route('vap_non_conformities.export.'.$format))->assertUnprocessable()->assertJsonValidationErrors('export');
        }
    }
}
