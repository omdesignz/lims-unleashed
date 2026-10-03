<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierAssessmentRequest;
use App\Models\Department;
use App\Models\InventoryItemSupplier;
use App\Models\InventorySupplierAssessment;
use App\Models\User;
use App\Services\SampleLaboratoryAccess;
use App\Support\SupplierAssessmentNotifier;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SupplierAssessmentController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $laboratoryAccess) {}

    public function index(): Response
    {
        abort_if(! auth()->user()->can('view_isuppliers'), 403, '');

        $assessments = InventorySupplierAssessment::query()
            ->where('lab_id', $this->laboratoryAccess->activeLabId())
            ->with([
                'supplier:id,name,address',
                'department:id,name',
                'assessedBy:id,name',
            ])
            ->latest('assessment_date')
            ->get();

        return Inertia::render('SupplierAssessments/Index', [
            'assessments' => $assessments,
            'suppliers' => InventoryItemSupplier::query()->active()->orderBy('name')->get(['id', 'name', 'address']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'summary' => [
                'total' => $assessments->count(),
                'due_reviews' => $assessments->filter(fn (InventorySupplierAssessment $assessment) => $assessment->next_review_at && $assessment->next_review_at->lte(now()->addDays(30)))->count(),
                'high_risk' => $assessments->whereIn('risk_level', ['high', 'critical'])->count(),
                'conditional' => $assessments->where('status', 'conditional')->count(),
                'approved' => $assessments->where('status', 'approved')->count(),
            ],
        ]);
    }

    public function store(SupplierAssessmentRequest $request, SupplierAssessmentNotifier $notifier): RedirectResponse
    {
        abort_if(! auth()->user()->can('add_isuppliers'), 403, '');

        $assessment = InventorySupplierAssessment::query()->create(
            array_merge($this->payload($request), ['lab_id' => $this->laboratoryAccess->activeLabId()])
        );

        $assessment->load('supplier');
        $notifier->notifySensitiveAssessment($assessment, auth()->user());

        return back()->with('success', 'Avaliação de fornecedor registada.');
    }

    public function update(SupplierAssessmentRequest $request, InventorySupplierAssessment $supplierAssessment, SupplierAssessmentNotifier $notifier): RedirectResponse
    {
        abort_if(! auth()->user()->can('edit_isuppliers'), 403, '');
        abort_unless($supplierAssessment->lab_id === $this->laboratoryAccess->activeLabId(), 404);

        $supplierAssessment->update(
            $this->payload($request)
        );

        $supplierAssessment->load('supplier');
        $notifier->notifySensitiveAssessment($supplierAssessment, auth()->user());

        return back()->with('success', 'Avaliação de fornecedor actualizada.');
    }

    public function destroy(InventorySupplierAssessment $supplierAssessment): RedirectResponse
    {
        abort_if(! auth()->user()->can('delete_isuppliers'), 403, '');
        abort_unless($supplierAssessment->lab_id === $this->laboratoryAccess->activeLabId(), 404);

        $supplierAssessment->delete();

        return back()->with('success', 'Avaliação de fornecedor arquivada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(SupplierAssessmentRequest $request): array
    {
        $validated = $request->validated();

        $scores = collect([
            $validated['delivery_score'] ?? null,
            $validated['quality_score'] ?? null,
            $validated['compliance_score'] ?? null,
            $validated['responsiveness_score'] ?? null,
        ])->filter(fn ($value) => $value !== null);

        $validated['assessed_by_user_id'] = auth()->id();
        $validated['approved_supplier'] = (bool) ($validated['approved_supplier'] ?? false);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['total_score'] = $scores->isEmpty()
            ? 0
            : (int) round($scores->avg() * 20);

        return $validated;
    }
}
