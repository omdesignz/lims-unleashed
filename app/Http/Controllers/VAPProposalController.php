<?php

namespace App\Http\Controllers;

use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Models\VAPProposalItem;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\Department;
use App\Models\Standard;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Support\ProposalWorkflowNotifier;
use App\Support\ReportStudioPdfBuilder;
use App\Settings\GeneralSettings;

class VAPProposalController extends Controller
{
    public function index(Request $request)
    {
        $query = VAPProposal::with(['customer', 'department', 'user'])
            ->withCount('items')
            ->latest();

        // Filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('proposal_no', 'like', "%{$search}%")
                  ->orWhere('proposal_year', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($customer) use ($search) {
                      $customer->where('name', 'like', "%{$search}%")
                               ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        $proposals = $query->paginate(20);

        // Statistics
        $stats = [
            'total' => VAPProposal::count(),
            'pending' => VAPProposal::where('status', 'PENDING')->count(),
            'accepted' => VAPProposal::where('status', 'ACCEPTED')->count(),
            'rejected' => VAPProposal::where('status', 'REJECTED')->count(),
            'expired' => VAPProposal::where('status', 'EXPIRED')->count(),
            'total_value' => VAPProposal::where('status', 'ACCEPTED')->sum('total'),
        ];

        return Inertia::render('VAPProposals/Index', [
            'proposals' => $proposals,
            'filters' => $request->only(['search', 'status']),
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        return Inertia::render('VAPProposals/Create', [
            'customers' => Customer::active()->get(['id', 'name', 'code']),
            'warehouses' => Warehouse::active()->get(['id', 'name']),
            'departments' => Department::active()->get(['id', 'name']),
            'templates' => VAPProposalTemplate::with('user')->get(),
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
            'items.*.item_id' => 'required',
            'items.*.item_description' => 'required|string|max:255',
            'items.*.standard_id' => 'nullable|exists:standards,id',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.itemable_type' => 'required|string',
            'items.*.itemable_id' => 'required|integer',
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
            // Generate proposal number
            // $proposalNo = $this->generateProposalNumber();

            // Create proposal with all calculated fields
            $proposal = VAPProposal::create([
                // 'seq' => VAPProposal::max('seq') + 1,
                // 'seq' => '',
                'proposal_year' => date('Y'),
                // 'proposal_no' => $proposalNo,
                'service_location' => $validated['service_location'],
                'customer_id' => $validated['customer_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'department_id' => $validated['department_id'],
                'user_id' => auth()->id(),
                'template_id' => $validated['template_id'],
                'status' => 'PENDING',
                'details' => [],
                'obs' => $validated['obs'] ?? null,
                'sub_total' => $validated['sub_total'],
                'total' => $validated['total'],
                'tax' => $validated['tax'] ?? 0,
                'discount' => $validated['discount'] ?? 0,
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
            foreach ($validated['items'] as $item) {
                // Calculate item totals if not provided
                $quantity = $item['qty'];
                $unitPrice = $item['unit_price'];
                $discountPercent = $item['discount_percentage'] ?? 0;
                
                $itemTotal = $quantity * $unitPrice;
                $discountAmount = $itemTotal * ($discountPercent / 100);
                $itemNet = $itemTotal - $discountAmount;
                
                // Use provided tax amount or calculate
                $taxAmount = $item['tax_amount'] ?? 0;
                if ($item['charge_tax'] ?? false && empty($item['tax_amount'])) {
                    $taxPercent = $item['tax_percentage'] ?? 0;
                    $taxAmount = $itemNet * ($taxPercent / 100);
                }

                $proposal->items()->create([
                    'item_id' => $item['item_id'] ?? null,
                    'item_description' => $item['item_description'],
                    'standard_id' => $item['standard_id'] ?? null,
                    'unit_id' => $item['unit_id'],
                    'qty' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $itemNet,
                    'discount_percentage' => $discountPercent,
                    'discount_amount' => $item['discount_amount'] ?? $discountAmount,
                    'discount_id' => $item['discount_id'] ?? 1,
                    'tax_percentage' => $item['tax_percentage'] ?? 0,
                    'tax_amount' => $taxAmount,
                    'tax_id' => $item['tax_id'] ?? null,
                    'charge_tax' => $item['charge_tax'] ?? false,
                    'itemable_id' => $item['itemable_id'] ?? null,
                    'itemable_type' => $item['itemable_type'] ?? null,
                    'withhold_tax' => $item['withhold_tax'] ?? false,
                    'exemption_id' => $item['exemption_id'] ?? null,
                    'exemption_code' => $item['exemption_code'] ?? null,
                    'obs' => $item['obs'] ?? null,
                ]);
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
            Log::error('Failed to create proposal: ' . $e->getMessage());
            DB::rollBack();
            return back()->with('error', 'Não foi possível criar a proposta.');
        }
    }

    public function show(VAPProposal $proposal)
    {
        $proposal->load([
            'customer',
            'warehouse',
            'department',
            'user',
            'template',
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
            'canSend' => $proposal->status === 'PENDING',
            'canRevise' => in_array($proposal->status, ['PENDING', 'SENT', 'VIEWED', 'REJECTED']),
        ]);
    }

    public function edit(VAPProposal $proposal)
    {
        if (!in_array($proposal->status, ['PENDING', 'SENT', 'VIEWED', 'REJECTED'])) {
            return redirect()->route('vap-proposals.show', $proposal)
                ->with('error', 'Cannot edit proposal in current status.');
        }

        $proposal->load('items');

        return Inertia::render('VAPProposals/Edit', [
            'proposal' => $proposal,
            'customers' => Customer::active()->get(['id', 'name', 'code']),
            'warehouses' => Warehouse::active()->get(['id', 'name']),
            'departments' => Department::active()->get(['id', 'name']),
            'templates' => VAPProposalTemplate::with('user')->get(),
            'units' => Unit::all(['id', 'code', 'description']),
            'standards' => Standard::all(['id', 'description', 'code']),
        ]);
    }

    // public function update(Request $request, VAPProposal $proposal)
    // {
    //     if (!in_array($proposal->status, ['PENDING', 'SENT', 'VIEWED', 'REJECTED'])) {
    //         return back()->with('error', 'Cannot update proposal in current status.');
    //     }

    //     $validated = $request->validate([
    //         'service_location' => 'required|string|max:255',
    //         'obs' => 'nullable|string',
    //         'tolerance_days' => 'required|integer|min:1|max:365',
    //         'revision_reason' => 'required|string|min:10',
    //         'sub_total' => 'required|numeric|min:0',
    //         'total' => 'required|numeric|min:0',
    //         'tax' => 'nullable|numeric|min:0',
    //         'discount' => 'nullable|numeric|min:0',
    //         'items' => 'required|array|min:1',
    //         'items.*.item_id' => 'nullable|exists:items,id',
    //         'items.*.item_description' => 'required|string|max:255',
    //         'items.*.itemable_type' => 'required|string',
    //         'items.*.itemable_id' => 'required|integer',
    //         'items.*.standard_id' => 'nullable|exists:standards,id',
    //         'items.*.unit_id' => 'required|exists:units,id',
    //         'items.*.qty' => 'required|numeric|min:0.01',
    //         'items.*.unit_price' => 'required|numeric|min:0',
    //         'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
    //         'items.*.discount_amount' => 'nullable|numeric|min:0',
    //         'items.*.discount_id' => 'nullable|integer',
    //         'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
    //         'items.*.tax_amount' => 'nullable|numeric|min:0',
    //         'items.*.tax_id' => 'nullable|exists:tax_types,id',
    //         'items.*.charge_tax' => 'boolean',
    //         'items.*.withhold_tax' => 'boolean',
    //         'items.*.exemption_id' => 'nullable|exists:tax_exemptions,id',
    //         'items.*.exemption_code' => 'nullable|string|max:50',
    //         'items.*.obs' => 'nullable|string',
    //     ]);

    //     DB::beginTransaction();

    //     try {
    //         // Create revision log
    //         activity()
    //             ->performedOn($proposal)
    //             ->causedBy(auth()->user())
    //             ->withProperties([
    //                 'old_values' => [
    //                     'obs' => $proposal->obs,
    //                     'service_location' => $proposal->service_location,
    //                     'tolerance_days' => $proposal->tolerance_days,
    //                     'total' => $proposal->total,
    //                     'sub_total' => $proposal->sub_total,
    //                     'tax' => $proposal->tax,
    //                     'discount' => $proposal->discount,
    //                 ],
    //                 'reason' => $validated['revision_reason'],
    //             ])
    //             ->log('revised');

    //         // Update proposal
    //         $proposal->update([
    //             'service_location' => $validated['service_location'],
    //             'obs' => $validated['obs'] ?? null,
    //             'tolerance_days' => $validated['tolerance_days'],
    //             'sub_total' => $validated['sub_total'],
    //             'total' => $validated['total'],
    //             'tax' => $validated['tax'] ?? 0,
    //             'discount' => $validated['discount'] ?? 0,
    //             'status' => 'REVISED',
    //             'is_original' => false,
    //         ]);

    //         // Delete old items
    //         $proposal->items()->delete();

    //         // Create new items
    //         foreach ($validated['items'] as $item) {
    //             // Calculate item totals
    //             $quantity = $item['qty'];
    //             $unitPrice = $item['unit_price'];
    //             $discountPercent = $item['discount_percentage'] ?? 0;
                
    //             $itemTotal = $quantity * $unitPrice;
    //             $discountAmount = $item['discount_amount'] ?? ($itemTotal * ($discountPercent / 100));
    //             $itemNet = $itemTotal - $discountAmount;
                
    //             // Calculate tax
    //             $taxAmount = $item['tax_amount'] ?? 0;
    //             if ($item['charge_tax'] ?? false && empty($item['tax_amount'])) {
    //                 $taxPercent = $item['tax_percentage'] ?? 0;
    //                 $taxAmount = $itemNet * ($taxPercent / 100);
    //             }

    //             $proposal->items()->create([
    //                 'item_id' => $item['item_id'] ?? null,
    //                 'item_description' => $item['item_description'],
    //                 'standard_id' => $item['standard_id'] ?? null,
    //                 'unit_id' => $item['unit_id'],
    //                 'qty' => $quantity,
    //                 'unit_price' => $unitPrice,
    //                 'total' => $itemNet,
    //                 'discount_percentage' => $discountPercent,
    //                 'discount_amount' => $discountAmount,
    //                 'discount_id' => $item['discount_id'] ?? 1,
    //                 'tax_percentage' => $item['tax_percentage'] ?? 0,
    //                 'tax_amount' => $taxAmount,
    //                 'tax_id' => $item['tax_id'] ?? null,
    //                 'charge_tax' => $item['charge_tax'] ?? false,
    //                 'itemable_id' => $item['itemable_id'] ?? null,
    //                 'itemable_type' => $item['itemable_type'] ?? null,
    //                 'withhold_tax' => $item['withhold_tax'] ?? false,
    //                 'exemption_id' => $item['exemption_id'] ?? null,
    //                 'exemption_code' => $item['exemption_code'] ?? null,
    //                 'obs' => $item['obs'] ?? null,
    //             ]);
    //         }

    //         DB::commit();

    //         return redirect()->route('vap-proposals.show', $proposal)
    //             ->with('success', 'Proposal revised successfully.');

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()->with('error', 'Failed to revise proposal: ' . $e->getMessage());
    //     }
    // }

    public function update(Request $request, VAPProposal $proposal, ProposalWorkflowNotifier $proposalWorkflowNotifier)
    {
        if (!in_array($proposal->status, ['PENDING', 'SENT', 'VIEWED', 'REJECTED'])) {
            return back()->with('error', 'Cannot update proposal in current status.');
        }

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
            'items.*.item_id' => 'required',
            'items.*.itemable_type' => 'required|string',
            'items.*.itemable_id' => 'required|integer',
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
            // Create revision log with detailed changes
            $oldItems = $proposal->items->toArray();
            
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
                        'total' => $validated['total'],
                        'sub_total' => $validated['sub_total'],
                        'tax' => $validated['tax'] ?? 0,
                        'discount' => $validated['discount'] ?? 0,
                        'withhold_tax' => $validated['withhold_tax'] ?? false,
                        'use_matrix_price' => $validated['use_matrix_price'] ?? true,
                        'items_count' => count($validated['items']),
                    ],
                    'reason' => $validated['revision_reason'],
                    'item_changes' => [
                        'removed' => array_diff(
                            array_column($oldItems, 'id'),
                            array_column($validated['items'], 'id')
                        ),
                        'added' => count($validated['items']) - count($oldItems),
                    ]
                ])
                ->log('revised');

            // Update proposal with all fields
            $proposal->update([
                'service_location' => $validated['service_location'],
                'obs' => $validated['obs'] ?? null,
                'tolerance_days' => $validated['tolerance_days'],
                'withhold_tax' => $validated['withhold_tax'] ?? false,
                'use_matrix_price' => $validated['use_matrix_price'] ?? true,
                'sub_total' => $validated['sub_total'],
                'total' => $validated['total'],
                'tax' => $validated['tax'] ?? 0,
                'discount' => $validated['discount'] ?? 0,
                'status' => 'REVISED',
                'is_original' => false,
            ]);

            // Delete old items
            $proposal->items()->delete();

            // Create new items with all fields
            foreach ($validated['items'] as $item) {
                // Calculate item totals if needed
                $quantity = $item['qty'];
                $unitPrice = $item['unit_price'];
                $discountPercent = $item['discount_percentage'] ?? 0;
                
                $itemTotal = $quantity * $unitPrice;
                $discountAmount = $item['discount_amount'] ?? ($itemTotal * ($discountPercent / 100));
                $itemNet = $itemTotal - $discountAmount;
                
                // Calculate tax if needed
                $taxAmount = $item['tax_amount'] ?? 0;
                if (($item['charge_tax'] ?? false) && empty($item['tax_amount'])) {
                    $taxPercent = $item['tax_percentage'] ?? 0;
                    $taxAmount = $itemNet * ($taxPercent / 100);
                }

                $proposal->items()->create([
                    'item_id' => $item['item_id'] ?? null,
                    'itemable_type' => $item['itemable_type'],
                    'itemable_id' => $item['itemable_id'],
                    'item_description' => $item['item_description'],
                    'standard_id' => $item['standard_id'] ?? null,
                    'unit_id' => $item['unit_id'],
                    'qty' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $itemNet,
                    'discount_percentage' => $discountPercent,
                    'discount_amount' => $discountAmount,
                    'discount_id' => $item['discount_id'] ?? 1,
                    'tax_percentage' => $item['tax_percentage'] ?? 0,
                    'tax_amount' => $taxAmount,
                    'tax_id' => $item['tax_id'] ?? null,
                    'charge_tax' => $item['charge_tax'] ?? false,
                    'withhold_tax' => $item['withhold_tax'] ?? false,
                    'exemption_id' => $item['exemption_id'] ?? null,
                    'exemption_code' => $item['exemption_code'] ?? null,
                    'obs' => $item['obs'] ?? null,
                ]);
            }

            // Update expiry date based on tolerance days
            $expiryDate = Carbon::now()->addDays($validated['tolerance_days']);
            $proposal->update(['expiry_date' => $expiryDate]);

            DB::commit();

            // Send notification if needed
            $proposalWorkflowNotifier->notifyRevised($proposal->fresh(['customer', 'warehouse', 'user']));

            return redirect()->route('vap-proposals.show', $proposal)
                ->with('success', 'Proposta revista com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log error
            Log::error('Failed to revise proposal: ' . $e->getMessage(), [
                'proposal_id' => $proposal->id,
                'user_id' => auth()->id(),
                'errors' => $e->getTrace()
            ]);
            
            return back()->with('error', 'Não foi possível rever a proposta.');
        }
    }

    public function send(VAPProposal $proposal, ProposalWorkflowNotifier $proposalWorkflowNotifier)
    {
        if ($proposal->status !== 'PENDING') {
            return back()->with('error', 'A proposta só pode ser enviada quando estiver pendente.');
        }

        if (! $proposal->file_path) {
            app()->call([$this, 'generatePdf'], ['proposal' => $proposal]);
            $proposal->refresh();
        }

        $proposal->update(['status' => 'SENT']);

        $proposalWorkflowNotifier->notifySent($proposal->fresh(['customer', 'warehouse', 'user']));

        activity()
            ->performedOn($proposal)
            ->causedBy(auth()->user())
            ->log('sent');

        return back()->with('success', 'Proposal has been marked as sent.');
    }

    public function generatePdf(VAPProposal $proposal, ReportStudioPdfBuilder $reportStudioPdfBuilder, GeneralSettings $settings)
    {
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
                $proposal
            );
        }

        $studioPayload = $reportStudioPdfBuilder->buildProposalPayload(
            $proposal,
            $parsedContent ?? '<p>Sem conteúdo configurado para esta proposta.</p>',
            $settings
        );

        $pdf = \PDF::loadView($studioPayload['view'], $studioPayload['data']);

        $filename = "Proposal-{$proposal->proposal_number}.pdf";
        
        // Save to storage
        $path = "vap-proposals/{$proposal->id}/{$filename}";
        Storage::put($path, $pdf->output());
        
        $proposal->update(['file_path' => $path]);

        return $pdf->download($filename);
    }

    public function destroy(VAPProposal $proposal)
    {
        if (!in_array($proposal->status, ['PENDING', 'REJECTED'])) {
            return back()->with('error', 'Cannot delete proposal in current status.');
        }

        $proposal->delete();

        return redirect()->route('vap-proposals.index')
            ->with('success', 'Proposal deleted successfully.');
    }

    public function accept(Request $request, VAPProposal $proposal, ProposalWorkflowNotifier $proposalWorkflowNotifier)
    {
        $validated = $request->validate([
            'confidentiality' => 'required|boolean',
            'impartiality' => 'required|boolean',
            'nondisclosure' => 'required|boolean',
        ]);

        if (!in_array($proposal->status, ['SENT', 'VIEWED', 'REVISED'])) {
            return response()->json([
                'success' => false,
                'message' => 'Proposal cannot be accepted in current status.'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Update compliance agreement
            $proposal->complianceAgreement()->update([
                'confidentiality' => $validated['confidentiality'],
                'impartiality' => $validated['impartiality'],
                'nondisclosure' => $validated['nondisclosure'],
                'acknowledged_at' => now(),
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

            activity()
                ->performedOn($proposal)
                ->withProperties(['client_ip' => $request->ip()])
                ->log('accepted');

            DB::commit();

            $proposalWorkflowNotifier->notifyAccepted($proposal->fresh(['customer', 'warehouse', 'user']));

            return response()->json([
                'success' => true,
                'message' => 'Proposta aceite com sucesso.',
                'redirect' => route('vap-proposals.public.thankyou', $proposal)
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
                'message' => 'Não foi possível aceitar a proposta.'
            ], 500);
        }
    }

    public function reject(Request $request, VAPProposal $proposal, ProposalWorkflowNotifier $proposalWorkflowNotifier)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        if (!in_array($proposal->status, ['SENT', 'VIEWED', 'REVISED'])) {
            return response()->json([
                'success' => false,
                'message' => 'Proposal cannot be rejected in current status.'
            ], 400);
        }

        $proposal->update([
            'status' => 'REJECTED',
            'obs' => ($proposal->obs ? $proposal->obs . "\n\n" : '') . "Rejection Reason: " . $validated['reason']
        ]);

        activity()
            ->performedOn($proposal)
            ->withProperties(['reason' => $validated['reason']])
            ->log('rejected');

        $proposalWorkflowNotifier->notifyRejected($proposal->fresh(['customer', 'warehouse', 'user']));

        return response()->json([
            'success' => true,
            'message' => 'Proposal rejected successfully.',
            'redirect' => route('vap-proposals.public.thankyou', $proposal)
        ]);
    }

    public function getWarehouse(Request $request)
    {
        $query = $request->get('q', '');
        $customerId = $request->get('customer_id');
        
        $warehouses = Warehouse::when($customerId, function($q) use ($customerId) {
            $q->where('customer_id', $customerId);
        })
        ->when($query, function($q) use ($query) {
            $q->where('address', 'like', "%{$query}%");
        })
        ->limit(10)
        ->get(['id', 'address']);
        
        return response()->json($warehouses);
    }

    public function getMatrix(Request $request)
    {
        $query = $request->get('q', '');
        
        $matrixes = \App\Models\Matrix::when($query, function($q) use ($query) {
            $q->where('description', 'like', "%{$query}%");
        })
        ->limit(10)
        ->get(['id', 'description', 'fixed_price as price']);
        
        return response()->json($matrixes);
    }

    public function getParameter(Request $request)
    {
        $query = $request->get('q', '');
        
        $parameters = \App\Models\Parameter::when($query, function($q) use ($query) {
            $q->where('name', 'like', "%{$query}%");
        })
        ->limit(10)
        ->get(['id', 'name', 'price']);
        
        return response()->json($parameters);
    }

    public function getLabCode(Request $request)
    {
        $query = $request->get('q', '');
        
        $labcodes = \App\Models\LabCode::when($query, function($q) use ($query) {
            $q->where('code', 'like', "%{$query}%");
        })
        ->limit(10)
        ->get(['id', 'code']);
        
        return response()->json($labcodes);
    }

    public function getLabCodeParameters(Request $request)
    {
        $codeId = $request->get('code_id');
        $useMatrixPrice = $request->get('use_matrix_price', true);
        
        if (!$codeId) {
            return response()->json([]);
        }
        
        // Get lab code with its parameters/matrixes
        $labCode = \App\Models\LabCode::with(['parameters', 'matrixes'])->find($codeId);
        
        if (!$labCode) {
            return response()->json([]);
        }
        
        $items = [];
        
        if ($useMatrixPrice && $labCode->matrixes->isNotEmpty()) {
            foreach ($labCode->matrixes as $matrix) {
                $items[] = [
                    'item_id' => $matrix->id,
                    'item_description' => $matrix->description,
                    'price' => $matrix->fixed_price,
                    'unit_id' => $matrix->unit_id ?? 1,
                    'qty' => 1,
                    'tax_percentage' => $matrix->tax_percentage ?? 0,
                    'charge_tax' => $matrix->charge_tax ?? true,
                    'tax_id' => $matrix->tax_id ?? null,
                    'exemption_id' => $matrix->exemption_id ?? null,
                    'exemption_code' => $matrix->exemption_code ?? null,
                ];
            }
        } elseif (!$useMatrixPrice && $labCode->parameters->isNotEmpty()) {
            foreach ($labCode->parameters as $parameter) {
                $items[] = [
                    'item_id' => $parameter->id,
                    'item_description' => $parameter->name,
                    'price' => $parameter->price,
                    'unit_id' => $parameter->unit_id ?? 1,
                    'qty' => 1,
                    'tax_percentage' => $parameter->tax_percentage ?? 0,
                    'charge_tax' => $parameter->charge_tax ?? true,
                    'tax_id' => $parameter->tax_id ?? null,
                    'exemption_id' => $parameter->exemption_id ?? null,
                    'exemption_code' => $parameter->exemption_code ?? null,
                ];
            }
        }
        
        return response()->json($items);
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
}
