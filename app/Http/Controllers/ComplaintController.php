<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Customer;
use App\Models\CustomerRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\ComplaintLoggedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $query = Complaint::query()
            ->with(['customer', 'warehouse', 'assignedTo', 'relatedRequest'])
            ->when($request->search, function ($builder, $search) {
                $builder->where(function ($nested) use ($search) {
                    $nested->where('reference', 'like', '%' . $search . '%')
                        ->orWhere('title', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->when($request->status, fn ($builder, $status) => $builder->where('status', $status))
            ->latest();

        return Inertia::render('Complaints/Index', [
            'complaints' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'status']),
            'stats' => [
                'total' => Complaint::count(),
                'open' => Complaint::whereIn('status', ['open', 'in_review'])->count(),
                'resolved' => Complaint::whereIn('status', ['resolved', 'closed'])->count(),
            ],
            'customers' => Customer::query()->select('id', 'name')->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->select('id', 'name', 'address')->orderBy('name')->get(),
            'users' => User::query()->select('id', 'name')->orderBy('name')->get(),
            'customerRequests' => CustomerRequest::query()->select('id', 'reference', 'title')->latest()->limit(100)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'severity' => 'required|in:low,medium,high,critical',
            'confidentiality_level' => 'required|in:public,internal,confidential,restricted',
            'reported_by_name' => 'required|string|max:255',
            'reported_by_email' => 'nullable|email|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'assigned_to_id' => 'nullable|exists:users,id',
            'related_request_id' => 'nullable|exists:customer_requests,id',
        ]);

        $complaint = Complaint::create(array_merge($validated, [
            'status' => 'open',
            'received_at' => now(),
        ]));

        $complaint->update([
            'reference' => 'CMP-' . now()->format('Y') . '-' . str_pad((string) $complaint->id, 6, '0', STR_PAD_LEFT),
        ]);

        $targets = User::role('admin')->get()
            ->merge($complaint->assignedTo ? collect([$complaint->assignedTo]) : collect())
            ->unique('id');

        if ($targets->isNotEmpty()) {
            Notification::send(
                $targets,
                new ComplaintLoggedNotification(
                    $complaint,
                    'Nova reclamação registada',
                    sprintf('A reclamação %s foi registada e requer análise.', $complaint->reference),
                    auth()->user()
                )
            );
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

    public function update(Request $request, Complaint $complaint)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_review,resolved,closed',
            'assigned_to_id' => 'nullable|exists:users,id',
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

        return redirect()->back()->with('success', 'Reclamação atualizada com sucesso.');
    }
}
