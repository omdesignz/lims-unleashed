<?php

namespace App\Support;

use App\Models\QualityCertificate;
use App\Models\ReportStudioTemplate;
use App\Models\VAPProposal;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\View;

class ReportStudioPdfBuilder
{
    public function buildAnalysisReportPayload(QualityCertificate $certificate, GeneralSettings $settings): array
    {
        $studio = ReportStudioTemplate::resolveDefaultFor('analysis');
        $layout = $studio?->layout_schema ?? [];
        $export = $studio?->export_settings ?? [];

        $headerContext = [
            'lab_name' => $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório',
            'document_code' => $certificate->code,
            'customer_name' => $certificate->customer?->name,
            'warehouse_name' => $certificate->warehouse?->name,
            'issue_date' => optional($certificate->created_at)->format('d/m/Y'),
        ];

        $bodyView = "PDFs.includes.analysisreport.templates.{$certificate->collection['result_id']}";
        $bodyHtml = View::exists($bodyView)
            ? View::make($bodyView, ['model' => $certificate])->render()
            : '<p>Sem conteúdo configurado para este modelo analítico.</p>';

        return [
            'view' => 'PDFs.studios.document',
            'data' => [
                'documentTitle' => $studio?->name ?? 'Relatório Analítico',
                'firstPageHeader' => $this->interpolate(
                    data_get($layout, 'first_page_header_html') ?: $this->defaultAnalysisFirstPageHeader($settings),
                    $headerContext
                ),
                'defaultHeader' => $this->interpolate(
                    data_get($layout, 'default_header_html') ?: '<div style="font-size:10px;">{{document_code}} · Emitido em {{issue_date}}</div>',
                    $headerContext
                ),
                'footerHtml' => $this->interpolate(
                    data_get($layout, 'footer_html') ?: $this->defaultAnalysisFooter($settings),
                    $headerContext
                ),
                'bodyHtml' => $bodyHtml,
                'styles' => data_get($layout, 'styles_css', ''),
                'backgroundImage' => data_get($layout, 'background_image_path'),
                'orientation' => data_get($export, 'orientation', 'P'),
                'format' => data_get($export, 'paper_size', 'A4'),
                'margins' => [
                    'top' => data_get($export, 'margin_top', 20),
                    'bottom' => data_get($export, 'margin_bottom', 24),
                    'left' => data_get($export, 'margin_left', 14),
                    'right' => data_get($export, 'margin_right', 14),
                    'first_top' => data_get($export, 'first_page_margin_top', 58),
                ],
            ],
        ];
    }

    public function buildExecutiveReportPayload(array $payload, GeneralSettings $settings): array
    {
        $studio = ReportStudioTemplate::resolveDefaultFor('executive');
        $layout = $studio?->layout_schema ?? [];
        $export = $studio?->export_settings ?? [];

        $headerContext = [
            'lab_name' => $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório',
            'document_code' => 'EXEC-' . now()->format('Ymd'),
            'issue_date' => now()->format('d/m/Y'),
        ];

        $bodyHtml = View::make('PDFs.studios.executive-summary-body', [
            'payload' => $payload,
        ])->render();

        return [
            'view' => 'PDFs.studios.document',
            'data' => [
                'documentTitle' => $studio?->name ?? 'Relatório Executivo',
                'firstPageHeader' => $this->interpolate(
                    data_get($layout, 'first_page_header_html') ?: '<h2 style="margin:0;">{{lab_name}}</h2><p style="margin-top:6px;">Relatório executivo emitido em {{issue_date}}</p>',
                    $headerContext
                ),
                'defaultHeader' => $this->interpolate(
                    data_get($layout, 'default_header_html') ?: '<div style="font-size:10px;">Resumo executivo · {{issue_date}}</div>',
                    $headerContext
                ),
                'footerHtml' => $this->interpolate(
                    data_get($layout, 'footer_html') ?: '<div style="font-size:9px; color:#475569;">Documento reservado para gestão. Página {PAGENO}/{nbpg}</div>',
                    $headerContext
                ),
                'bodyHtml' => $bodyHtml,
                'styles' => data_get($layout, 'styles_css', ''),
                'backgroundImage' => data_get($layout, 'background_image_path'),
                'orientation' => data_get($export, 'orientation', 'P'),
                'format' => data_get($export, 'paper_size', 'A4'),
                'margins' => [
                    'top' => data_get($export, 'margin_top', 20),
                    'bottom' => data_get($export, 'margin_bottom', 20),
                    'left' => data_get($export, 'margin_left', 14),
                    'right' => data_get($export, 'margin_right', 14),
                    'first_top' => data_get($export, 'first_page_margin_top', 42),
                ],
            ],
        ];
    }

    public function buildProposalPayload(VAPProposal $proposal, string $parsedContent, GeneralSettings $settings): array
    {
        $studio = ReportStudioTemplate::resolveDefaultFor('proposal');
        $layout = $studio?->layout_schema ?? [];
        $export = $studio?->export_settings ?? [];

        $headerContext = [
            'lab_name' => $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório',
            'document_code' => $proposal->proposal_no ?: $proposal->proposal_number ?: ('PROP-' . $proposal->id),
            'customer_name' => $proposal->customer?->name,
            'issue_date' => optional($proposal->created_at)->format('d/m/Y'),
            'expiry_date' => optional($proposal->expiry_date)->format('d/m/Y'),
        ];

        $bodyHtml = View::make('PDFs.studios.proposal-body', [
            'proposal' => $proposal,
            'parsedContent' => $parsedContent,
        ])->render();

        return [
            'view' => 'PDFs.studios.document',
            'data' => [
                'documentTitle' => $studio?->name ?? 'Proposta Comercial',
                'firstPageHeader' => $this->interpolate(
                    data_get($layout, 'first_page_header_html') ?: $this->defaultProposalFirstPageHeader($settings),
                    $headerContext
                ),
                'defaultHeader' => $this->interpolate(
                    data_get($layout, 'default_header_html') ?: '<div style="font-size:10px;">{{document_code}} · {{customer_name}}</div>',
                    $headerContext
                ),
                'footerHtml' => $this->interpolate(
                    data_get($layout, 'footer_html') ?: $this->defaultProposalFooter(),
                    $headerContext
                ),
                'bodyHtml' => $bodyHtml,
                'styles' => data_get($layout, 'styles_css', ''),
                'backgroundImage' => data_get($layout, 'background_image_path'),
                'orientation' => data_get($export, 'orientation', 'P'),
                'format' => data_get($export, 'paper_size', 'A4'),
                'margins' => [
                    'top' => data_get($export, 'margin_top', 20),
                    'bottom' => data_get($export, 'margin_bottom', 22),
                    'left' => data_get($export, 'margin_left', 14),
                    'right' => data_get($export, 'margin_right', 14),
                    'first_top' => data_get($export, 'first_page_margin_top', 56),
                ],
            ],
        ];
    }

    private function interpolate(string $template, array $data): string
    {
        $replacements = collect($data)
            ->mapWithKeys(fn ($value, $key) => ['{{' . $key . '}}' => (string) ($value ?? '')])
            ->all();

        return strtr($template, $replacements);
    }

    private function defaultAnalysisFirstPageHeader(GeneralSettings $settings): string
    {
        $labName = e($settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório');

        return <<<HTML
<div style="text-align:center; border-bottom:1px solid #111827; padding-bottom:10px;">
    <h2 style="margin:0; font-size:16px;">{$labName}</h2>
    <p style="margin:6px 0 0; font-size:10px;">Relatório de análise emitido com controlo documental e paginação integral.</p>
</div>
HTML;
    }

    private function defaultAnalysisFooter(GeneralSettings $settings): string
    {
        $contact = e($settings->app_contact ?: $settings->app_email ?: $settings->app_client_email ?: '');

        return <<<HTML
<div style="font-size:9px; color:#334155; border-top:1px solid #cbd5e1; padding-top:6px;">
    <div>{$contact}</div>
    <div>Documento controlado · Página {PAGENO}/{nbpg}</div>
</div>
HTML;
    }

    private function defaultProposalFirstPageHeader(GeneralSettings $settings): string
    {
        $labName = e($settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório');

        return <<<HTML
<div style="border-bottom:1px solid #0f172a; padding-bottom:10px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
        <div>
            <h2 style="margin:0; font-size:16px;">{$labName}</h2>
            <p style="margin:6px 0 0; font-size:10px;">Proposta comercial com enquadramento técnico, decisão de regra e condições documentadas.</p>
        </div>
        <div style="text-align:right; font-size:10px;">
            <div><strong>{{document_code}}</strong></div>
            <div>Emitida em {{issue_date}}</div>
        </div>
    </div>
</div>
HTML;
    }

    private function defaultProposalFooter(): string
    {
        return <<<HTML
<div style="font-size:9px; color:#334155; border-top:1px solid #cbd5e1; padding-top:6px;">
    <div>Documento controlado de proposta comercial · Página {PAGENO}/{nbpg}</div>
</div>
HTML;
    }
}
