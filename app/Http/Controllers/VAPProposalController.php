<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Department;
use App\Models\LabCode;
use App\Models\Matrix;
use App\Models\Parameter;
use App\Models\Standard;
use App\Models\Unit;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Models\Warehouse;
use App\Settings\GeneralSettings;
use App\Support\ProposalWorkflowNotifier;
use App\Support\ReportStudioPdfBuilder;
use App\Support\ReportStudioPdfRenderer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class VAPProposalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->string('status')->value() ?: 'all';
        $search = $request->string('search')->value();
        $templateId = $request->integer('template_id') ?: null;
        $period = in_array($request->integer('period'), [7, 30, 90], true)
            ? $request->integer('period')
            : 30;

        $query = VAPProposal::with([
            'customer:id,name,code',
            'department:id,name',
            'user:id,name',
            'template:id,name,category',
        ])
            ->withCount('items')
            ->withSum('items', 'tax_amount')
            ->withSum('items', 'discount_amount')
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($templateId) {
            $query->where('template_id', $templateId);
        }

        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('proposal_no', 'like', "%{$search}%")
                    ->orWhere('proposal_year', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customer) use ($search) {
                        $customer->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        $proposals = $query->paginate(20)->withQueryString();

        $statsQuery = VAPProposal::query()
            ->when($templateId, fn ($builder) => $builder->where('template_id', $templateId));

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'pending' => (clone $statsQuery)->where('status', 'PENDING')->count(),
            'accepted' => (clone $statsQuery)->where('status', 'ACCEPTED')->count(),
            'rejected' => (clone $statsQuery)->where('status', 'REJECTED')->count(),
            'expired' => (clone $statsQuery)->where('status', 'EXPIRED')->count(),
            'total_value' => (clone $statsQuery)->where('status', 'ACCEPTED')->sum('total'),
        ];

        $dailyActivity = VAPProposal::query()
            ->selectRaw('DATE(created_at) as proposal_date')
            ->selectRaw('COUNT(*) as created_count')
            ->selectRaw("SUM(CASE WHEN status = 'ACCEPTED' THEN 1 ELSE 0 END) as accepted_count")
            ->when($templateId, fn ($builder) => $builder->where('template_id', $templateId))
            ->where('created_at', '>=', now()->subDays($period)->startOfDay())
            ->groupBy('proposal_date')
            ->orderBy('proposal_date')
            ->get();

        $chartSeries = [
            [
                'name' => 'Propostas criadas',
                'data' => $dailyActivity
                    ->map(fn ($row) => [
                        Carbon::parse($row->proposal_date)->timestamp * 1000,
                        (int) $row->created_count,
                    ])
                    ->values()
                    ->all(),
            ],
            [
                'name' => 'Propostas aceites',
                'data' => $dailyActivity
                    ->map(fn ($row) => [
                        Carbon::parse($row->proposal_date)->timestamp * 1000,
                        (int) $row->accepted_count,
                    ])
                    ->values()
                    ->all(),
            ],
        ];

        return Inertia::render('VAPProposals/Index', [
            'proposals' => $proposals,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'template_id' => $templateId,
                'period' => $period,
            ],
            'stats' => $stats,
            'chartSeries' => $chartSeries,
            'selectedTemplate' => $templateId
                ? VAPProposalTemplate::query()->select(['id', 'name', 'category'])->find($templateId)
                : null,
        ]);
    }

    public function create()
    {
        return Inertia::render('VAPProposals/Create', [
            'customers' => Customer::active()->get(['id', 'name', 'code']),
            'warehouses' => Warehouse::active()->get(['id', 'name']),
            'departments' => Department::active()->get(['id', 'name']),
            'templates' => VAPProposalTemplate::query()
                ->with('user')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'units' => Unit::all(['id', 'code', 'description']),
            'standards' => Standard::all(['id', 'description', 'code']),
            'nextProposalNo' => $this->generateProposalNumber(),
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'department_id' => 'required|exists:departments,id',
            'template_id' => 'required|exists:proposal_templates,id',
            'service_location' => 'required|string|max:255',
            'obs' => 'nullable|string',
            'tolerance_days' => 'required|integer|min:1|max:365',
            'withhold_tax' => 'boolean',
            'use_matrix_price' => 'boolean',
            'tax' => 'nullable|numeric|min:0',
            'sub_total' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable',
            'items.*.item_description' => 'required|string|max:255',
            'items.*.standard_id' => 'nullable|exists:standards,id',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.itemable_type' => 'nullable|string',
            'items.*.itemable_id' => 'nullable|integer',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.discount_id' => 'nullable|integer',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_amount' => 'nullable|numeric|min:0',
            'items.*.tax_id' => 'nullable|exists:tax_types,id',
            'items.*.charge_tax' => 'boolean',
            'items.*.withhold_tax' => 'boolean',
            'items.*.exemption_id' => 'nullable|exists:tax_exemptions,id',
            'items.*.exemption_code' => 'nullable|string|max:50',
            'items.*.obs' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $normalizedItems = $this->normalizeProposalItems($validated['items']);
            $totals = $this->calculateProposalTotals($normalizedItems);

            // Create proposal with all calculated fields
            $proposal = VAPProposal::create([
                'proposal_year' => date('Y'),
                'service_location' => $validated['service_location'],
                'customer_id' => $validated['customer_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'department_id' => $validated['department_id'],
                'user_id' => auth()->id(),
                'template_id' => $validated['template_id'],
                'status' => 'PENDING',
                'details' => [],
                'obs' => $validated['obs'] ?? null,
                'sub_total' => $totals['sub_total'],
                'total' => $totals['total'],
                'discount' => $totals['discount'],
                'unique_hash' => Str::uuid(),
                'tolerance_days' => $validated['tolerance_days'],
                'withhold_tax' => $validated['withhold_tax'] ?? false,
                'use_matrix_price' => $validated['use_matrix_price'] ?? true,
                'withholding_tax_amount' => 0, // Will be calculated if needed
                'withholding_tax_percentage' => 0,
                'global_discount_amount' => 0,
                'global_discount_percentage' => 0,
                'converted_to_invoice' => false,
            ]);

            // Create items with all fields
            foreach ($normalizedItems as $item) {
                $proposal->items()->create($item);
            }

            // Create compliance agreement record
            $proposal->complianceAgreement()->create([
                'confidentiality' => false,
                'impartiality' => false,
                'nondisclosure' => false,
            ]);

            DB::commit();

            return redirect()->route('vap-proposals.show', $proposal)
                ->with('success', 'Proposta criada com sucesso. Já pode enviá-la ao cliente.');

        } catch (\Exception $e) {
            Log::error('Failed to create proposal: '.$e->getMessage());
            DB::rollBack();

            if (app()->environment('testing')) {
                throw $e;
            }

            return back()->with('error', 'Não foi possível criar a proposta.');
        }
    }

    public function show(VAPProposal $proposal, GeneralSettings $settings)
    {
        $proposal->load([
            'customer',
            'warehouse',
            'department',
            'user',
            'template.user',
            'items.standard',
            'items.unit',
            'complianceAgreement',
            'complianceAgreementLogs',
            'discountCategory',
        ]);

        // Get revision history
        $revisions = $proposal->activities()
            ->whereIn('event', ['updated', 'revised'])
            ->with('causer')
            ->latest()
            ->get();

        return Inertia::render('VAPProposals/Show', [
            'proposal' => $proposal,
            'revisions' => $revisions,
            'parsedTemplateContent' => $proposal->template?->content
                ? VAPProposalTemplate::parseContent($proposal->template->content, $proposal, $settings)
                : null,
            'canSend' => $proposal->status === 'PENDING',
            'canRevise' => in_array($proposal->status, ['PENDING', 'SENT', 'VIEWED', 'REJECTED']),
        ]);
    }

    public function edit(VAPProposal $proposal)
    {
        if (! in_array($proposal->status, ['PENDING', 'SENT', 'VIEWED', 'REJECTED'])) {
            return redirect()->route('vap-proposals.show', $proposal)
                ->with('error', 'A proposta não pode ser editada no estado actual.');
        }

        $proposal->load('items');

        return Inertia::render('VAPProposals/Edit', [
            'proposal' => $proposal,
            'customers' => Customer::active()->get(['id', 'name', 'code']),
            'warehouses' => Warehouse::active()->get(['id', 'name']),
            'departments' => Department::active()->get(['id', 'name']),
            'templates' => VAPProposalTemplate::query()
                ->with('user')
                ->where(function ($query) use ($proposal): void {
                    $query->where('is_active', true);

                    if ($proposal->template_id !== null) {
                        $query->orWhere('id', $proposal->template_id);
                    }
                })
                ->orderBy('name')
                ->get(),
            'units' => Unit::all(['id', 'code', 'description']),
            'standards' => Standard::all(['id', 'description', 'code']),
        ]);
    }

    public function update(Request $request, VAPProposal $proposal, ProposalWorkflowNotifier $proposalWorkflowNotifier)
    {
        if (! in_array($proposal->status, ['PENDING', 'SENT', 'VIEWED', 'REJECTED'])) {
            return back()->with('error', 'A proposta não pode ser actualizada no estado actual.');
        }

        $previousPdfPath = $proposal->file_path;

        $validated = $request->validate([
            'service_location' => 'required|string|max:255',
            'obs' => 'nullable|string',
            'tolerance_days' => 'required|integer|min:1|max:365',
            'revision_reason' => 'required|string|min:10',
            'withhold_tax' => 'boolean',
            'use_matrix_price' => 'boolean',
            'sub_total' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable',
            'items.*.itemable_type' => 'nullable|string',
            'items.*.itemable_id' => 'nullable|integer',
            'items.*.item_description' => 'required|string|max:255',
            'items.*.standard_id' => 'nullable|exists:standards,id',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.discount_id' => 'nullable|integer',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_amount' => 'nullable|numeric|min:0',
            'items.*.tax_id' => 'nullable|exists:tax_types,id',
            'items.*.charge_tax' => 'boolean',
            'items.*.withhold_tax' => 'boolean',
            'items.*.exemption_id' => 'nullable|exists:tax_exemptions,id',
            'items.*.exemption_code' => 'nullable|string|max:50',
            'items.*.obs' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $normalizedItems = $this->normalizeProposalItems($validated['items']);
            $totals = $this->calculateProposalTotals($normalizedItems);

            // Create revision log with detailed changes
            $oldItems = $proposal->items->toArray();

            try {
                activity()
                    ->performedOn($proposal)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'old_values' => [
                            'obs' => $proposal->obs,
                            'service_location' => $proposal->service_location,
                            'tolerance_days' => $proposal->tolerance_days,
                            'total' => $proposal->total,
                            'sub_total' => $proposal->sub_total,
                            'tax' => $proposal->tax ?? 0,
                            'discount' => $proposal->discount ?? 0,
                            'withhold_tax' => $proposal->withhold_tax,
                            'use_matrix_price' => $proposal->use_matrix_price,
                            'items_count' => count($oldItems),
                        ],
                        'new_values' => [
                            'obs' => $validated['obs'] ?? null,
                            'service_location' => $validated['service_location'],
                            'tolerance_days' => $validated['tolerance_days'],
                            'total' => $totals['total'],
                            'sub_total' => $totals['sub_total'],
                            'tax' => $totals['tax'],
                            'discount' => $totals['discount'],
                            'withhold_tax' => $validated['withhold_tax'] ?? false,
                            'use_matrix_price' => $validated['use_matrix_price'] ?? true,
                            'items_count' => count($normalizedItems),
                        ],
                        'reason' => $validated['revision_reason'],
                        'item_changes' => [
                            'removed' => array_diff(
                                array_column($oldItems, 'id'),
                                array_column($validated['items'], 'id')
                            ),
                            'added' => count($normalizedItems) - count($oldItems),
                        ],
                    ])
                    ->log('revised');
            } catch (\Throwable $exception) {
                report($exception);
            }

            // Update proposal with all fields
            $proposal->update([
                'service_location' => $validated['service_location'],
                'obs' => $validated['obs'] ?? null,
                'tolerance_days' => $validated['tolerance_days'],
                'withhold_tax' => $validated['withhold_tax'] ?? false,
                'use_matrix_price' => $validated['use_matrix_price'] ?? true,
                'sub_total' => $totals['sub_total'],
                'total' => $totals['total'],
                'discount' => $totals['discount'],
                'status' => 'REVISED',
                'is_original' => false,
                'file_path' => null,
            ]);

            // Delete old items
            $proposal->items()->delete();

            // Create new items with all fields
            foreach ($normalizedItems as $item) {
                $proposal->items()->create($item);
            }

            // Update expiry date based on tolerance days
            $expiryDate = Carbon::now()->addDays($validated['tolerance_days']);
            $proposal->update(['expiry_date' => $expiryDate]);

            DB::commit();

            if ($previousPdfPath) {
                try {
                    Storage::delete($previousPdfPath);
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }

            // Send notification if needed
            $proposalWorkflowNotifier->notifyRevised($proposal->fresh(['customer', 'warehouse', 'user']));

            return redirect()->route('vap-proposals.show', $proposal)
                ->with('success', 'Proposta revista com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();

            // Log error
            Log::error('Failed to revise proposal: '.$e->getMessage(), [
                'proposal_id' => $proposal->id,
                'user_id' => auth()->id(),
                'errors' => $e->getTrace(),
            ]);

            return back()->with('error', 'Não foi possível rever a proposta.');
        }
    }

    public function send(VAPProposal $proposal, ProposalWorkflowNotifier $proposalWorkflowNotifier)
    {
        if (! in_array($proposal->status, ['PENDING', 'REVISED'], true)) {
            return back()->with('error', 'A proposta só pode ser enviada quando estiver pendente ou revista.');
        }

        if (! $proposal->file_path) {
            app()->call([$this, 'generatePdf'], ['proposal' => $proposal]);
            $proposal->refresh();
        }

        $proposal->update(['status' => 'SENT']);

        $proposalWorkflowNotifier->notifySent($proposal->fresh(['customer', 'warehouse', 'user']));

        try {
            activity()
                ->performedOn($proposal)
                ->causedBy(auth()->user())
                ->log('sent');
        } catch (\Throwable $exception) {
            report($exception);
        }

        return back()->with('success', 'Proposta marcada como enviada e pronta para acompanhamento.');
    }

    public function generatePdf(
        VAPProposal $proposal,
        ReportStudioPdfBuilder $reportStudioPdfBuilder,
        ReportStudioPdfRenderer $reportStudioPdfRenderer,
        GeneralSettings $settings
    ) {
        $proposal->load([
            'customer',
            'warehouse',
            'department',
            'user',
            'template',
            'complianceAgreement',
            'items.standard',
            'items.unit',
        ]);

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

        $proposal->update(['file_path' => $path]);

        return response($renderedPdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Report-Studio-Renderer' => $renderedPdf['renderer'],
        ]);
    }

    public function destroy(VAPProposal $proposal)
    {
        if (! in_array($proposal->status, ['PENDING', 'REJECTED'])) {
            return back()->with('error', 'A proposta só pode ser eliminada quando estiver pendente ou rejeitada.');
        }

        $proposal->delete();

        return redirect()->route('vap-proposals.index')
            ->with('success', 'Proposta eliminada com sucesso.');
    }

    public function accept(Request $request, VAPProposal $proposal, ProposalWorkflowNotifier $proposalWorkflowNotifier)
    {
        $validated = $request->validate([
            'confidentiality' => 'required|boolean',
            'impartiality' => 'required|boolean',
            'nondisclosure' => 'required|boolean',
        ]);

        if (! in_array($proposal->status, ['SENT', 'VIEWED', 'REVISED'])) {
            return response()->json([
                'success' => false,
                'message' => 'A proposta não pode ser aceite no estado actual.',
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Update compliance agreement
            $proposal->complianceAgreement()->updateOrCreate([
                'proposal_id' => $proposal->id,
            ], [
                'confidentiality' => $validated['confidentiality'],
                'impartiality' => $validated['impartiality'],
                'nondisclosure' => $validated['nondisclosure'],
                'acknowledged_at' => now(),
                'rejected_at' => null,
                'rejection_reason' => null,
                'client_ip' => $request->ip(),
            ]);

            // Create log entry
            $proposal->complianceAgreementLogs()->create([
                'confidentiality' => $validated['confidentiality'],
                'impartiality' => $validated['impartiality'],
                'acknowledged_at' => now(),
                'client_ip' => $request->ip(),
            ]);

            // Update proposal status
            $proposal->update(['status' => 'ACCEPTED']);

            try {
                activity()
                    ->performedOn($proposal)
                    ->withProperties(['client_ip' => $request->ip()])
                    ->log('accepted');
            } catch (\Throwable $exception) {
                report($exception);
            }

            DB::commit();

            $proposalWorkflowNotifier->notifyAccepted($proposal->fresh(['customer', 'warehouse', 'user']));

            return response()->json([
                'success' => true,
                'message' => 'Proposta aceite com sucesso.',
                'redirect' => route('vap-proposals.public.thankyou', $proposal->unique_hash),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to accept VAP proposal.', [
                'proposal_id' => $proposal->id,
                'message' => $e->getMessage(),
            ]);

            if (app()->environment('testing')) {
                throw $e;
            }

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível aceitar a proposta.',
            ], 500);
        }
    }

    public function reject(Request $request, VAPProposal $proposal, ProposalWorkflowNotifier $proposalWorkflowNotifier)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        if (! in_array($proposal->status, ['SENT', 'VIEWED', 'REVISED'])) {
            return response()->json([
                'success' => false,
                'message' => 'A proposta não pode ser rejeitada no estado actual.',
            ], 400);
        }

        DB::beginTransaction();

        try {
            $proposal->complianceAgreement()->updateOrCreate([
                'proposal_id' => $proposal->id,
            ], [
                'confidentiality' => false,
                'impartiality' => false,
                'nondisclosure' => false,
                'acknowledged_at' => null,
                'rejected_at' => now(),
                'rejection_reason' => $validated['reason'],
                'client_ip' => $request->ip(),
            ]);

            $proposal->update([
                'status' => 'REJECTED',
                'obs' => ($proposal->obs ? $proposal->obs."\n\n" : '').'Motivo da rejeição: '.$validated['reason'],
            ]);

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();

            report($exception);

            if (app()->environment('testing')) {
                throw $exception;
            }

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível rejeitar a proposta.',
            ], 500);
        }

        try {
            activity()
                ->performedOn($proposal)
                ->withProperties(['reason' => $validated['reason']])
                ->log('rejected');
        } catch (\Throwable $exception) {
            report($exception);
        }

        $proposalWorkflowNotifier->notifyRejected($proposal->fresh(['customer', 'warehouse', 'user']));

        return response()->json([
            'success' => true,
            'message' => 'Proposta rejeitada com sucesso.',
            'redirect' => route('vap-proposals.public.thankyou', $proposal->unique_hash),
        ]);
    }

    public function getWarehouse(Request $request)
    {
        $query = $request->string('q')->value();
        $customerId = $request->integer('customer_id') ?: null;

        $warehouses = Warehouse::query()
            ->when($customerId, function ($builder) use ($customerId): void {
                $builder->where('customer_id', $customerId);
            })
            ->when($query, function ($builder) use ($query): void {
                $builder->where(function ($nested) use ($query): void {
                    $nested->where('address', 'like', "%{$query}%")
                        ->orWhere('name', 'like', "%{$query}%");
                });
            })
            ->orderBy('address')
            ->limit(20)
            ->get(['id', 'name', 'address'])
            ->map(function (Warehouse $warehouse): array {
                $label = $warehouse->address ?: ($warehouse->name ?: "Armazém #{$warehouse->id}");

                return [
                    'id' => $warehouse->id,
                    'value' => $warehouse->id,
                    'label' => $label,
                    'name' => $warehouse->name,
                    'address' => $warehouse->address,
                ];
            })
            ->values();

        return response()->json($warehouses);
    }

    public function getProposal(Request $request)
    {
        $query = $request->string('q')->value();

        return response()->json(
            VAPProposal::query()
                ->when($query, function ($builder) use ($query): void {
                    $builder->where(function ($nested) use ($query): void {
                        $nested->where('proposal_no', 'like', "%{$query}%")
                            ->orWhere('service_location', 'like', "%{$query}%")
                            ->orWhere('status', 'like', "%{$query}%");
                    });
                })
                ->latest('id')
                ->limit(20)
                ->get(['id', 'proposal_no', 'proposal_year', 'service_location', 'status', 'created_at'])
                ->map(function (VAPProposal $proposal): array {
                    $label = trim(implode(' · ', array_filter([
                        $proposal->proposal_number,
                        $proposal->service_location,
                    ])));

                    return [
                        'id' => $proposal->id,
                        'value' => $proposal->id,
                        'label' => $label !== '' ? $label : "Proposta #{$proposal->id}",
                        'proposal_no' => $proposal->proposal_no,
                        'proposal_number' => $proposal->proposal_number,
                        'proposal_year' => $proposal->proposal_year,
                        'service_location' => $proposal->service_location,
                        'status' => $proposal->status,
                        'created_at' => optional($proposal->created_at)->format('d/m/Y'),
                    ];
                })
                ->values()
        );
    }

    public function getLabCode(Request $request)
    {
        $query = $request->string('q')->value();

        return response()->json(
            LabCode::query()
                ->when($query, function ($builder) use ($query): void {
                    $builder->where('code', 'like', "%{$query}%");
                })
                ->orderByDesc('id')
                ->limit(20)
                ->get(['id', 'code'])
                ->map(fn (LabCode $labCode): array => [
                    'id' => $labCode->id,
                    'value' => $labCode->id,
                    'label' => $labCode->code ?: "Código #{$labCode->id}",
                    'code' => $labCode->code,
                ])
                ->values()
        );
    }

    public function getMatrix(Request $request)
    {
        $query = $request->string('q')->value();

        $matrixes = Matrix::query()
            ->when($query, function ($builder) use ($query): void {
                $builder->where(function ($nested) use ($query): void {
                    $nested->where('description', 'like', "%{$query}%")
                        ->orWhere('code', 'like', "%{$query}%");
                });
            })
            ->orderBy('description')
            ->limit(20)
            ->get(['id', 'code', 'description', 'fixed_price', 'tax_id', 'charge_tax', 'tax_percentage', 'exemption_id', 'exemption_code', 'withhold_tax'])
            ->map(fn (Matrix $matrix): array => [
                'id' => $matrix->id,
                'value' => $matrix->id,
                'label' => $matrix->description ?: ($matrix->code ?: "Matriz #{$matrix->id}"),
                'code' => $matrix->code,
                'description' => $matrix->description,
                'price' => (float) $matrix->fixed_price,
                'tax_id' => $matrix->tax_id,
                'charge_tax' => (bool) $matrix->charge_tax,
                'tax_percentage' => (float) $matrix->tax_percentage,
                'exemption_id' => $matrix->exemption_id,
                'exemption_code' => $matrix->exemption_code,
                'withhold_tax' => (bool) $matrix->withhold_tax,
            ])
            ->values();

        return response()->json($matrixes);
    }

    public function getParameter(Request $request)
    {
        $query = $request->string('q')->value();

        $parameters = Parameter::query()
            ->where('active', true)
            ->when($query, function ($builder) use ($query): void {
                $builder->where(function ($nested) use ($query): void {
                    $nested->where('name', 'like', "%{$query}%")
                        ->orWhere('code', 'like', "%{$query}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'code', 'price', 'tax_id', 'charge_tax', 'tax_percentage', 'exemption_id', 'exemption_code', 'withhold_tax'])
            ->map(fn (Parameter $parameter): array => [
                'id' => $parameter->id,
                'value' => $parameter->id,
                'label' => $parameter->name ?: ($parameter->code ?: "Parâmetro #{$parameter->id}"),
                'name' => $parameter->name,
                'code' => $parameter->code,
                'price' => (float) $parameter->price,
                'tax_id' => $parameter->tax_id,
                'charge_tax' => (bool) $parameter->charge_tax,
                'tax_percentage' => (float) $parameter->tax_percentage,
                'exemption_id' => $parameter->exemption_id,
                'exemption_code' => $parameter->exemption_code,
                'withhold_tax' => (bool) $parameter->withhold_tax,
            ])
            ->values();

        return response()->json($parameters);
    }

    public function getLabCodeParameters(Request $request)
    {
        $codeId = $request->integer('code_id');
        $useMatrixPrice = $request->boolean('use_matrix_price', true);

        if (! $codeId) {
            return response()->json([]);
        }

        $labCode = LabCode::query()
            ->with('collection.product.matrix')
            ->find($codeId);

        if (! $labCode) {
            return response()->json([]);
        }

        $matrix = $labCode->collection?->product?->matrix;

        if (! $matrix) {
            return response()->json([]);
        }

        if ($useMatrixPrice) {
            return response()->json([
                $this->matrixOptionPayload($matrix),
            ]);
        }

        $parameterIds = Matrix::query()->parameters($matrix->id);

        return response()->json(
            Parameter::query()
                ->whereIn('id', $parameterIds)
                ->where('active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (Parameter $parameter) => $this->parameterOptionPayload($parameter))
                ->values()
        );
    }

    private function generateProposalNumber(): string
    {
        $year = date('Y');
        $lastProposal = VAPProposal::where('proposal_year', $year)
            ->orderBy('seq', 'desc')
            ->first();

        $nextNumber = $lastProposal ? $lastProposal->seq + 1 : 1;

        return str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeProposalItems(array $items): array
    {
        return collect($items)
            ->map(function (array $item): array {
                $quantity = (float) $item['qty'];
                $unitPrice = (float) $item['unit_price'];
                $grossTotal = $quantity * $unitPrice;
                $discountPercent = (float) ($item['discount_percentage'] ?? 0);
                $discountAmount = isset($item['discount_amount']) && (float) $item['discount_amount'] > 0
                    ? min((float) $item['discount_amount'], $grossTotal)
                    : $grossTotal * ($discountPercent / 100);
                $netTotal = max($grossTotal - $discountAmount, 0);
                $taxPercent = (float) ($item['tax_percentage'] ?? 0);
                $taxAmount = 0.0;

                if ($item['charge_tax'] ?? false) {
                    $taxAmount = isset($item['tax_amount']) && (float) $item['tax_amount'] > 0
                        ? (float) $item['tax_amount']
                        : ($netTotal * ($taxPercent / 100));
                }

                return [
                    'item_id' => $item['item_id'] ?? null,
                    'itemable_type' => $item['itemable_type'] ?? null,
                    'itemable_id' => $item['itemable_id'] ?? null,
                    'item_description' => $item['item_description'],
                    'standard_id' => $item['standard_id'] ?? null,
                    'unit_id' => $item['unit_id'],
                    'qty' => round($quantity, 2),
                    'unit_price' => round($unitPrice, 2),
                    'total' => round($netTotal, 2),
                    'discount_percentage' => round($discountPercent, 2),
                    'discount_amount' => round($discountAmount, 2),
                    'discount_id' => $item['discount_id'] ?? 1,
                    'tax_percentage' => round($taxPercent, 2),
                    'tax_amount' => round($taxAmount, 2),
                    'tax_id' => $item['tax_id'] ?? null,
                    'charge_tax' => (bool) ($item['charge_tax'] ?? false),
                    'withhold_tax' => (bool) ($item['withhold_tax'] ?? false),
                    'exemption_id' => $item['exemption_id'] ?? null,
                    'exemption_code' => $item['exemption_code'] ?? null,
                    'obs' => $item['obs'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{sub_total: float, discount: float, tax: float, total: float}
     */
    private function calculateProposalTotals(array $items): array
    {
        $subTotal = collect($items)->sum(fn (array $item): float => (float) $item['total']);
        $discount = collect($items)->sum(fn (array $item): float => (float) $item['discount_amount']);
        $tax = collect($items)->sum(fn (array $item): float => (float) $item['tax_amount']);

        return [
            'sub_total' => round($subTotal, 2),
            'discount' => round($discount, 2),
            'tax' => round($tax, 2),
            'total' => round($subTotal + $tax, 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function matrixOptionPayload(Matrix $matrix): array
    {
        $label = $matrix->description ?: ($matrix->code ?: "Matriz #{$matrix->id}");

        return [
            'id' => $matrix->id,
            'value' => $matrix->id,
            'label' => $label,
            'item_id' => $matrix->id,
            'itemable_type' => Matrix::class,
            'itemable_id' => $matrix->id,
            'item_description' => $label,
            'name' => $label,
            'description' => $label,
            'price' => (float) $matrix->fixed_price,
            'unit_id' => Unit::query()->value('id'),
            'qty' => 1,
            'tax_percentage' => (float) $matrix->tax_percentage,
            'charge_tax' => (bool) $matrix->charge_tax,
            'tax_id' => $matrix->tax_id,
            'exemption_id' => $matrix->exemption_id,
            'exemption_code' => $matrix->exemption_code,
            'withhold_tax' => (bool) $matrix->withhold_tax,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parameterOptionPayload(Parameter $parameter): array
    {
        $label = $parameter->name ?: ($parameter->code ?: "Parâmetro #{$parameter->id}");

        return [
            'id' => $parameter->id,
            'value' => $parameter->id,
            'label' => $label,
            'item_id' => $parameter->id,
            'itemable_type' => Parameter::class,
            'itemable_id' => $parameter->id,
            'item_description' => $label,
            'name' => $label,
            'description' => $parameter->description ?: $label,
            'price' => (float) $parameter->price,
            'unit_id' => Unit::query()->value('id'),
            'qty' => 1,
            'tax_percentage' => (float) $parameter->tax_percentage,
            'charge_tax' => (bool) $parameter->charge_tax,
            'tax_id' => $parameter->tax_id,
            'exemption_id' => $parameter->exemption_id,
            'exemption_code' => $parameter->exemption_code,
            'withhold_tax' => (bool) $parameter->withhold_tax,
        ];
    }
}
