<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\User;
use App\Services\SampleLaboratoryAccess;
use App\Support\NotificationTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $laboratoryAccess) {}

    public function index(Request $request): Response
    {
        $labId = $this->laboratoryAccess->activeLabId();
        $query = Complaint::query()
            ->where('lab_id', $labId)
            ->with(['customer', 'assignedTo'])
            ->when($request->search, function ($builder, $search) {
                $builder->where(function ($nested) use ($search) {
                    $nested->where('reference', 'like', '%'.$search.'%')
                        ->orWhere('title', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->when($request->status, fn ($builder, $status) => $builder->where('status', $status))
            ->latest();

        return Inertia::render('Complaints/Index', [
            'complaints' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'status']),
            'stats' => [
                'total' => Complaint::where('lab_id', $labId)->count(),
                'open' => Complaint::where('lab_id', $labId)->whereIn('status', ['open', 'in_review'])->count(),
                'resolved' => Complaint::where('lab_id', $labId)->whereIn('status', ['resolved', 'closed'])->count(),
            ],
        ]);
    }

    public function store(Request $request, NotificationTemplateService $templates): RedirectResponse
    {
        $labId = $this->laboratoryAccess->activeLabId();
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'severity' => 'required|in:low,medium,high,critical',
            'confidentiality_level' => 'required|in:public,internal,confidential,restricted',
            'reported_by_name' => 'required|string|max:255',
            'reported_by_email' => 'nullable|email|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'assigned_to_id' => ['nullable', Rule::exists('lab_user', 'user_id')->where('lab_id', $labId)],
            'related_request_id' => ['prohibited'],
        ]);

        $complaint = Complaint::create(array_merge($validated, [
            'lab_id' => $labId,
            'status' => 'open',
            'received_at' => now(),
        ]));

        $complaint->update([
            'reference' => 'CMP-'.now()->format('Y').'-'.str_pad((string) $complaint->id, 6, '0', STR_PAD_LEFT),
        ]);

        $targets = User::role('admin')->whereIn('id', DB::table('lab_user')->where('lab_id', $labId)->select('user_id'))->get()
            ->merge($complaint->assignedTo ? collect([$complaint->assignedTo]) : collect())
            ->unique('id');

        if ($targets->isNotEmpty()) {
            $templates->notify($targets, 'quality.complaint.created', [
                'lab_id' => $labId,
                'document_number' => $complaint->reference,
                'severity' => $complaint->severity,
                'document_url' => route('complaints.index'),
            ]);
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($complaint)
            ->withProperties([
                'complaint_reference' => $complaint->reference,
                'status' => $complaint->status,
            ])
            ->log('Registou uma reclamação ISO 17025');

        return redirect()->back()->with('success', 'Reclamação registada com sucesso.');
    }

    public function update(Request $request, Complaint $complaint): RedirectResponse
    {
        $labId = $this->laboratoryAccess->activeLabId();
        abort_unless($complaint->lab_id === $labId, 404);

        $validated = $request->validate([
            'status' => 'required|in:open,in_review,resolved,closed',
            'assigned_to_id' => ['nullable', Rule::exists('lab_user', 'user_id')->where('lab_id', $labId)],
            'root_cause' => 'nullable|string',
            'corrective_action' => 'nullable|string',
            'follow_up_notes' => 'nullable|string',
        ]);

        if ($validated['status'] === 'in_review' && ! $complaint->acknowledged_at) {
            $validated['acknowledged_at'] = now();
        }

        if (in_array($validated['status'], ['resolved', 'closed'], true)) {
            $validated['resolved_at'] = now();
        }

        $complaint->update($validated);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($complaint)
            ->withProperties([
                'complaint_reference' => $complaint->reference,
                'status' => $complaint->status,
            ])
            ->log('Atualizou uma reclamação ISO 17025');

        return redirect()->back()->with('success', 'Reclamação actualizada com sucesso.');
    }
}
