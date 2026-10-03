<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\LabNetworkAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetProposalLaboratoryContext
{
    public function __construct(private readonly LabNetworkAccess $access) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('portal.*', 'vap-proposals.public.*', 'proposals.api.accept', 'proposals.api.reject')) {
            return $next($request);
        }

        $user = $request->user('web');
        $labs = $user instanceof User
            ? $this->access->memberships($user)
            : collect();
        $lab = $labs->firstWhere('id', $request->hasSession() ? $request->session()->get('active_lab_id') : null) ?? $labs->first();
        $request->attributes->set('proposal_laboratory_id', (int) ($lab?->id ?? 0));

        try {
            if ($user instanceof User && $request->routeIs('invoices.*', 'creditnotes.*', 'receipts.*', 'invoiceitems.*', 'quotes.*', 'quoteitems.*', 'importcertificates.*', 'exportcertificates.*')) {
                abort_unless($lab, 403, 'É necessária uma associação directa ao laboratório.');
                $module = match (true) {
                    $request->routeIs('creditnotes.*') => 'credit_notes',
                    $request->routeIs('receipts.*') => 'receipts',
                    $request->routeIs('quotes.*', 'quoteitems.*') => 'quotes',
                    $request->routeIs('importcertificates.*') => 'import_certificates',
                    $request->routeIs('exportcertificates.*') => 'export_certificates',
                    default => 'invoices',
                };
                $ability = match ($request->route()->getActionMethod()) {
                    'create', 'store' => 'add',
                    'edit', 'update', 'changeStatusToPaid' => 'edit',
                    'destroy' => 'delete',
                    'restore' => 'restore',
                    default => 'view',
                };
                abort_unless($user?->can($ability.'_'.$module), 403);
                if (in_array($request->route()->getActionMethod(), ['convertToInvoice', 'issueInvoice', 'getConvertToInvoiceModal', 'getIssueInvoiceModal'], true)) {
                    abort_unless($user?->can('add_invoices'), 403);
                }
            }
            if ($request->routeIs('vap-proposals.templates.*', 'proposaltemplates.*')) {
                $permission = match ($request->route()->getActionMethod()) {
                    'create', 'store' => 'add_proposal_templates',
                    'previewDraftPdf' => $user?->can('add_proposal_templates')
                        ? 'add_proposal_templates' : 'edit_proposal_templates',
                    'edit', 'update', 'toggleStatus' => 'edit_proposal_templates',
                    'destroy' => 'delete_proposal_templates',
                    'restore' => 'restore_proposal_templates',
                    'import' => 'import_proposal_templates',
                    'export', 'exportPdf' => 'export_proposal_templates',
                    default => 'view_proposal_templates',
                };
                abort_unless($user?->can($permission), 403);
            }

            if ($request->routeIs('proposals.*', 'vap-proposals.*', 'proposalcomplianceagreements.*')
                && ! $request->routeIs('vap-proposals.templates.*')) {
                abort_unless($lab, 403, 'É necessária uma associação directa ao laboratório.');
                $action = $request->route()->getActionMethod();
                $permission = match ($action) {
                    'create', 'store' => 'add_proposals',
                    'edit', 'update', 'accept', 'reject', 'send' => 'edit_proposals',
                    'destroy' => 'delete_proposals',
                    'restore' => 'restore_proposals',
                    default => 'view_proposals',
                };
                abort_unless($user?->can($permission), 403);
            }

            return $next($request);
        } finally {
            $request->attributes->remove('proposal_laboratory_id');
        }
    }
}
