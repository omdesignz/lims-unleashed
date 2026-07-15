<?php

namespace App\Http\Controllers;

use App\Models\ManagementReview;
use App\Models\User;
use App\Support\NotificationTemplateService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ManagementReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = ManagementReview::query()
            ->with(['conductedBy', 'approvedBy'])
            ->when($request->status, fn ($builder, $status) => $builder->where('status', $status))
            ->latest('review_date');

        return Inertia::render('ManagementReviews/Index', [
            'reviews' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['status']),
            'stats' => [
                'planned' => ManagementReview::where('status', 'planned')->count(),
                'in_progress' => ManagementReview::where('status', 'in_progress')->count(),
                'completed' => ManagementReview::where('status', 'completed')->count(),
            ],
            'users' => User::query()->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, NotificationTemplateService $templates)
    {
        $validated = $request->validate([
            'review_date' => 'required|date',
            'scope' => 'nullable|string|max:255',
            'summary' => 'nullable|string',
            'decisions' => 'nullable|string',
            'risks_and_opportunities' => 'nullable|string',
            'improvements' => 'nullable|string',
            'conducted_by_id' => 'nullable|exists:users,id',
        ]);

        $review = ManagementReview::create(array_merge($validated, [
            'status' => 'planned',
        ]));

        $review->update([
            'reference' => 'MR-'.now()->format('Y').'-'.str_pad((string) $review->id, 6, '0', STR_PAD_LEFT),
        ]);

        $targets = User::role('admin')->get()
            ->merge($review->conductedBy ? collect([$review->conductedBy]) : collect())
            ->unique('id');

        if ($targets->isNotEmpty()) {
            $templates->notify($targets, 'quality.management_review.scheduled', [
                'document_number' => $review->reference,
                'review_date' => $review->review_date?->format('d/m/Y') ?? 'data em aberto',
                'document_url' => route('management-reviews.index'),
            ]);
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($review)
            ->withProperties([
                'management_review_reference' => $review->reference,
                'status' => $review->status,
            ])
            ->log('Registou uma revisão pela gestão');

        return redirect()->back()->with('success', 'Revisão pela gestão registada com sucesso.');
    }

    public function update(Request $request, ManagementReview $managementReview, NotificationTemplateService $templates)
    {
        $validated = $request->validate([
            'status' => 'required|in:planned,in_progress,completed',
            'summary' => 'nullable|string',
            'decisions' => 'nullable|string',
            'risks_and_opportunities' => 'nullable|string',
            'improvements' => 'nullable|string',
            'approved_by_id' => 'nullable|exists:users,id',
        ]);

        if ($validated['status'] === 'completed' && ! empty($validated['approved_by_id'])) {
            $validated['approved_at'] = now();
        }

        $managementReview->update($validated);

        if ($managementReview->status === 'completed') {
            $templates->notify(User::role('admin')->get()->unique('id'), 'quality.management_review.completed', [
                'document_number' => $managementReview->reference,
                'document_url' => route('management-reviews.index'),
            ]);
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($managementReview)
            ->withProperties([
                'management_review_reference' => $managementReview->reference,
                'status' => $managementReview->status,
            ])
            ->log('Atualizou uma revisão pela gestão');

        return redirect()->back()->with('success', 'Revisão pela gestão actualizada com sucesso.');
    }
}
