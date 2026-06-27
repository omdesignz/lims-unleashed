<?php

namespace App\Http\Controllers;

use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioPdfBuilder;
use App\Support\ReportStudioPdfRenderer;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class VAPPublicProposalController extends Controller
{
    public function show($hash, GeneralSettings $settings)
    {
        $proposal = VAPProposal::with([
            'customer',
            'department',
            'warehouse',
            'user',
            'items.standard',
            'items.unit',
            'complianceAgreement',
            'template',
        ])->where('unique_hash', $hash)
            ->firstOrFail();

        // Track view
        if ($proposal->status === 'SENT') {
            $proposal->update(['status' => 'VIEWED']);
            activity()
                ->performedOn($proposal)
                ->withProperties(['client_ip' => request()->ip()])
                ->log('viewed_by_client');
        }

        $parsedTemplateContent = $proposal->template?->content
            ? VAPProposalTemplate::parseContent($proposal->template->content, $proposal, $settings)
            : null;

        return Inertia::render('Public/ProposalShow', [
            'proposal' => $proposal,
            'parsedTemplateContent' => $parsedTemplateContent,
            'isExpired' => $proposal->expiry_date && now()->gt($proposal->expiry_date),
            'company' => [
                'name' => $settings->app_client_lab_name ?: $settings->app_client_name ?: $settings->app_name ?: config('app.name'),
                'tagline' => $settings->app_client_lab_slogan ?: $settings->app_slogan,
                'logo_url' => $settings->app_logo_url,
                'address' => $settings->app_client_address,
                'phone' => $settings->app_client_contact ?: $settings->app_contact,
                'email' => $settings->app_client_email ?: $settings->app_email,
                'nif' => $settings->app_client_nif ?: $settings->app_nif,
                'lab_director' => $settings->app_client_lab_director,
                'bank_name' => $settings->app_bank_name,
                'bank_account_name' => $settings->app_bank_account_name,
                'bank_account_number' => $settings->app_bank_account_number,
                'bank_iban' => $settings->app_bank_iban,
                'bank_swift' => $settings->app_bank_swift,
                'bank_details' => $settings->app_bank_details,
                'document_keywords' => $settings->app_document_keywords,
            ],
        ]);
    }

    public function thankyou(VAPProposal $proposal)
    {
        return Inertia::render('Public/ThankYou', [
            'proposal' => $proposal->only(['proposal_number', 'status']),
        ]);
    }

    public function downloadPdf2($hash)
    {
        $proposal = VAPProposal::where('unique_hash', $hash)->firstOrFail();

        if (! $proposal->file_path || ! Storage::exists($proposal->file_path)) {
            abort(404, 'Proposal PDF not found.');
        }

        return Storage::download($proposal->file_path);
    }

    public function downloadPdf(
        $hash,
        ReportStudioPdfBuilder $reportStudioPdfBuilder,
        ReportStudioPdfRenderer $reportStudioPdfRenderer,
        GeneralSettings $settings
    ) {

        $proposal = VAPProposal::where('unique_hash', $hash)->firstOrFail();

        $proposal->load([
            'customer',
            'department',
            'warehouse',
            'user',
            'template',
            'items.standard',
            'items.unit',
            'complianceAgreement',
        ]);

        // Add days_until_expiry to proposal
        $proposal->days_until_expiry = $proposal->expiry_date
            ? now()->diffInDays($proposal->expiry_date, false)
            : null;

        // Parse template content if needed
        $parsedContent = null;
        if ($proposal->template && $proposal->template->content) {
            $parsedContent = VAPProposalTemplate::parseContent(
                $proposal->template->content,
                $proposal,
                $settings
            );
        }

        $studioPayload = $reportStudioPdfBuilder->buildProposalPayload(
            $proposal,
            $parsedContent ?? '<p>Sem conteúdo configurado para esta proposta.</p>',
            $settings
        );

        $filename = str($proposal->proposal_number)->slug('-')->prepend('Proposta-')->append('.pdf')->value();
        $renderedPdf = $reportStudioPdfRenderer->renderDocument('proposal', $studioPayload, $filename);

        // Save to storage
        $path = "vap-proposals/{$proposal->id}/{$filename}";
        Storage::put($path, $renderedPdf['content']);

        // Update proposal with file path
        $proposal->update(['file_path' => $path]);

        return response($renderedPdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'X-Report-Studio-Renderer' => $renderedPdf['renderer'],
        ]);
    }
}
