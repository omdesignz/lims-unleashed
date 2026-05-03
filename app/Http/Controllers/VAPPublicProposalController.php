<?php

namespace App\Http\Controllers;

use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use PDF;

class VAPPublicProposalController extends Controller
{
    public function show($hash)
    {
        $proposal = VAPProposal::with([
            'customer',
            'department',
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

        return Inertia::render('Public/ProposalShow', [
            'proposal' => $proposal,
            'isExpired' => $proposal->expiry_date && now()->gt($proposal->expiry_date),
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
        
        if (!$proposal->file_path || !Storage::exists($proposal->file_path)) {
            abort(404, 'Proposal PDF not found.');
        }

        return Storage::download($proposal->file_path);
    }

    public function downloadPdf($hash)
    {

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
                $proposal
            );
        }

        $data = [
            'proposal' => $proposal,
            'parsedContent' => $parsedContent,
        ];

        // dd($data);

        // Generate PDF using mpdf
        $pdf = PDF::loadView('proposals.pdf', $data);
        
        $filename = "Proposta-{$proposal->proposal_number}.pdf";
        
        // Save to storage
        $path = "vap-proposals/{$proposal->id}/{$filename}";
        Storage::put($path, $pdf->output());
        
        // Update proposal with file path
        $proposal->update(['file_path' => $path]);

        return $pdf->stream($filename);
    }
}
