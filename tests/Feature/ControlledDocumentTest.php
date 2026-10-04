<?php

namespace Tests\Feature;

use App\Models\VAPSampleEntry;
use App\Settings\GeneralSettings;
use App\Support\ControlledDocument;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * The blocks every generated laboratory document is built from, and the page
 * they are printed on.
 */
class ControlledDocumentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_a_grid_prints_only_what_was_recorded_two_pairs_to_a_line(): void
    {
        $grid = ControlledDocument::keyValueGrid([
            ['label' => 'Lote', 'value' => 'LT-1'],
            ['label' => 'Origem', 'value' => null],
            ['label' => 'Marca', 'value' => ''],
            ['label' => 'Diluição', 'value' => '0'],
            ['label' => 'Fornecedor', 'value' => '<b>Acme</b>', 'flag' => 'c'],
            ['label' => 'Estado', 'value' => "Íntegra\nSelada", 'wide' => true],
        ]);

        $this->assertSame(3, substr_count($grid, '<tr>'));
        $this->assertStringNotContainsString('Origem', $grid);
        $this->assertStringNotContainsString('Marca', $grid);
        $this->assertStringContainsString('>0</td>', $grid);
        $this->assertStringContainsString('&lt;b&gt;Acme&lt;/b&gt;', $grid);
        $this->assertStringContainsString('<span class="doc-flag">(c)</span>', $grid);
        $this->assertStringContainsString('colspan="3">Íntegra<br>', $grid);
        $this->assertSame('', ControlledDocument::keyValueGrid([['label' => 'Lote', 'value' => null]]));
    }

    public function test_sections_number_only_the_ones_with_content(): void
    {
        $html = ControlledDocument::sections([
            ['Cliente', '<p>A</p>'],
            ['Amostragem', ''],
            ['Resultados', '<p>B</p>'],
            ['Autorização', '<p>C</p>', true],
        ]);

        preg_match_all('/<span class="doc-section-number">(\d+)\.<\/span> ([^<]+)</', $html, $matches);

        $this->assertSame(['1', '2', '3'], $matches[1]);
        $this->assertSame(['Cliente', 'Resultados', 'Autorização'], $matches[2]);
        $this->assertSame(1, substr_count($html, 'class="doc-section doc-keep"'));
        $this->assertSame('', ControlledDocument::section(1, 'Vazio', '   '));
        $this->assertStringNotContainsString('doc-section-number', ControlledDocument::section('', 'Sem número', '<p>x</p>'));
    }

    public function test_statements_and_signatures_are_escaped_and_leave_room_to_sign(): void
    {
        $statements = ControlledDocument::statements(['Primeira <i>declaração</i>.', '', 'Segunda.']);
        $this->assertSame(2, substr_count($statements, '<li>'));
        $this->assertStringContainsString('&lt;i&gt;declaração&lt;/i&gt;', $statements);
        $this->assertSame('', ControlledDocument::statements(['', ' ']));

        $signatures = ControlledDocument::authorisation([
            ['name' => 'Ana <Técnica>', 'role' => 'Responsável', 'date' => 'Autorizado em 01/10/2026'],
            ['name' => null, 'caption' => 'Entregue por'],
            ['name' => null],
        ]);

        // The line to sign on is the bottom rule of a cell, which both renderers draw.
        $this->assertSame(2, substr_count($signatures, 'class="doc-auth-sign"'));
        $this->assertStringContainsString('Ana &lt;Técnica&gt;', $signatures);
        $this->assertStringContainsString('Entregue por', $signatures);
        $this->assertSame('', ControlledDocument::authorisation([['name' => null]]));
        $this->assertSame('<div class="doc-end">*** Fim do registo ***</div>', ControlledDocument::endMark('registo'));
    }

    public function test_page_furnishings_identify_the_laboratory_the_document_and_the_page(): void
    {
        $settings = app(GeneralSettings::class);
        $furnishings = ControlledDocument::furnishings(
            $settings,
            'Registo de Recepção de Amostra',
            'AM-2026-<1>',
            [['N.º', 'AM-2026-<1>'], ['Emissão', '04/10/2026']],
            '04/10/2026',
            'Aviso do documento.',
            'AM-2026-1',
        );

        foreach ($furnishings as $surface => $html) {
            $this->assertDoesNotMatchRegularExpression('/\{\{[a-z_]+\}\}/', $html, $surface);
            $this->assertStringNotContainsString('<1>', $html, $surface);
        }

        $this->assertStringContainsString(e(ControlledDocument::laboratoryName($settings)), $furnishings['letterhead']);
        $this->assertStringContainsString('class="doc-control-title">Registo de Recepção de Amostra</td>', $furnishings['letterhead']);
        $this->assertStringContainsString('AM-2026-&lt;1&gt;', $furnishings['letterhead']);
        $this->assertStringContainsString('src="data:image/', $furnishings['letterhead']);
        $this->assertStringContainsString('Registo de Recepção de Amostra n.º AM-2026-&lt;1&gt;', $furnishings['running']);
        $this->assertStringContainsString('Página {PAGENO} de {nbpg}', $furnishings['footer']);
        $this->assertStringContainsString('Aviso do documento.', $furnishings['footer']);
        $this->assertStringContainsString('Emitido em 04/10/2026', $furnishings['footer']);

        // Without verification text there is no code and no empty cell for one.
        $plain = ControlledDocument::furnishings($settings, 'Relatório', 'R-1', [['N.º', 'R-1']], '04/10/2026');
        $this->assertStringNotContainsString('doc-letterhead-qr', $plain['letterhead']);
        $this->assertSame('', ControlledDocument::verificationCodeHtml('  '));
    }

    public function test_only_small_local_images_are_embedded(): void
    {
        $directory = sys_get_temp_dir().'/controlled-document-'.bin2hex(random_bytes(6));
        mkdir($directory);

        try {
            file_put_contents($directory.'/mark.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
            file_put_contents($directory.'/notes.txt', 'not an image');

            $this->assertStringStartsWith('data:image/png;base64,', (string) ControlledDocument::imageDataUri($directory.'/mark.png'));
            $this->assertNull(ControlledDocument::imageDataUri($directory.'/notes.txt'));
            $this->assertNull(ControlledDocument::imageDataUri($directory.'/missing.png'));
        } finally {
            array_map('unlink', glob($directory.'/*') ?: []);
            rmdir($directory);
        }
    }

    public function test_the_sample_receipt_is_printed_as_a_controlled_document(): void
    {
        $entry = VAPSampleEntry::factory()->createQuietly([
            'name' => 'Água de consumo',
            'received_by_label' => 'Rui Recepção',
            'collected_by_lab' => false,
            'obs' => null,
            'client_submitted_info' => ['conditioning_status' => 'restricted', 'packaging_condition' => 'Tampa danificada'],
        ]);
        $entry->load(['customer', 'lab', 'department', 'warehouse', 'packaging', 'receivedBy']);

        $html = view('PDFs.sample-entry', [
            'sample' => $entry,
            'settings' => app(GeneralSettings::class),
            'date' => '04/10/2026',
            'time' => '10:00:00',
        ])->render();

        // Letterhead on the first page, the running header after it, the footer on all of them.
        foreach (['header: doc-letterhead;', 'header: doc-running;', 'footer: doc-footer;', '<htmlpageheader name="doc-letterhead">', '<htmlpagefooter name="doc-footer">'] as $furnishing) {
            $this->assertStringContainsString($furnishing, $html);
        }
        $this->assertStringContainsString('class="doc-control-title">Registo de Recepção de Amostra</td>', $html);
        $this->assertStringContainsString($entry->code, $html);
        $this->assertStringContainsString('Página {PAGENO} de {nbpg}', $html);

        // Condition on receipt and who received it (ISO/IEC 17025, 7.4).
        foreach (['Água de consumo', 'Aceite com restrições', 'Tampa danificada', 'Realizada pelo cliente', 'Rui Recepção', 'Recebido pelo laboratório', 'Fim do registo'] as $text) {
            $this->assertStringContainsString($text, $html);
        }

        // Nothing recorded, nothing printed: no filler values and no internal workflow state.
        foreach (['N/D', 'N/A', 'POR_INICIAR', 'Observações'] as $absent) {
            $this->assertStringNotContainsString($absent, $html);
        }
        preg_match_all('/<span class="doc-section-number">(\d+)\.<\/span>/', $html, $numbers);
        $this->assertSame(range(1, count($numbers[1])), array_map('intval', $numbers[1]));
    }

    public function test_every_rewritten_document_view_uses_the_shared_page(): void
    {
        $views = [
            'PDFs/sample-entry', 'PDFs/collection_term', 'PDFs/parameters_to_analyze', 'PDFs/sample-discard', 'PDFs/contractguide',
            'exports/non-conformities/details-pdf', 'exports/non-conformities/pdf', 'exports/order', 'exports/inventory-need',
            'exports/maintenance/tasks', 'exports/maintenance/calendar', 'exports/revision-history', 'exports/revision-comparison',
            'exports/chart', 'reports/inventory-export', 'reports/consumption', 'reports/inventory-generic', 'reports/batch_genealogy',
        ];

        foreach ($views as $view) {
            $source = file_get_contents(resource_path('views/'.$view.'.blade.php'));

            $this->assertStringContainsString("@extends('PDFs.partials.controlled-layout')", $source, $view);
            $this->assertStringContainsString('$documentTitle', $source, $view);
            $this->assertStringContainsString('$documentNumber', $source, $view);
            $this->assertStringContainsString('$issueDate', $source, $view);
            // One stylesheet for all: a view carries no palette or layout of its own.
            $this->assertDoesNotMatchRegularExpression('/<style|border-radius|linear-gradient|#[0-9a-fA-F]{6}\b/', $source, $view);
            $this->assertStringNotContainsString("'N/D'", $source, $view);
            $this->assertStringNotContainsString("'N/A'", $source, $view);
        }
    }
}
