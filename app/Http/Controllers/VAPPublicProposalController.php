<?php

namespace App\Http\Controllers;

use App\Actions\DownloadPublicProposalPdf;
use App\Actions\RecordPublicProposalView;
use App\Http\Resources\PublicProposalResource;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Services\PublicProposalAccess;
use App\Settings\GeneralSettings;
use Illuminate\Http\Response;
use Inertia\Inertia;

class VAPPublicProposalController extends Controller
{
    public function show(string $hash, GeneralSettings $settings, RecordPublicProposalView $view, PublicProposalAccess $access): \Inertia\Response
    {
        $proposal = $view->execute($hash, request()->ip())->load([
            'customer',
            'department',
            'warehouse',
            'user',
            'items.standard',
            'items.unit',
            'complianceAgreement',
            'template',
        ]);

        $parsedTemplateContent = $proposal->template?->content
            ? VAPProposalTemplate::parseContent($proposal->template->content, $proposal, $settings)
            : null;
        $publicProposal = PublicProposalResource::make($proposal)->resolve();
        $access->current($proposal);

        return Inertia::render('Public/ProposalShow', [
            'proposal' => $publicProposal,
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

    public function thankyou(VAPProposal $proposal, PublicProposalAccess $access): \Inertia\Response
    {
        $proposal = $access->current($proposal);

        return Inertia::render('Public/ThankYou', [
            'proposal' => $proposal->only(['proposal_number', 'status']),
            'proposalUrl' => route('vap-proposals.public.show', $proposal->unique_hash),
        ]);
    }

    public function downloadPdf(string $hash, DownloadPublicProposalPdf $download): Response
    {
        $rendered = $download->execute($hash);

        return response($rendered['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$rendered['filename'].'"',
            'X-Report-Studio-Renderer' => $rendered['renderer'],
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
