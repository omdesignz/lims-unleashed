<?php

namespace App\Http\Controllers;

use App\Models\CriteriaRating;
use App\Models\CustomerRequest;
use App\Models\InventoryOrder;
use App\Models\MaintenanceTask;
use App\Models\PaidService;
use App\Models\QualityCertificate;
use App\Models\Rating;
use App\Models\RatingRequest;
use App\Models\User;
use App\Models\VAPProposal;
use App\Models\VAPSampleEntry;
use App\Support\QualityModuleNotifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RatingController extends Controller
{
    public function index(): Response
    {
        $ratings = Rating::query()
            ->with(['user', 'rater'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Ratings/Index', [
            'ratings' => $ratings,
            'stats' => $this->ratingStats(),
            'charts' => $this->ratingCharts(),
        ]);
    }

    public function store(Request $request, string $rateableType, int $rateableId = 0): RedirectResponse
    {
        return $this->storeForRater(
            request: $request,
            rateableType: $rateableType,
            rateableId: $rateableId,
            rater: $request->user(),
            channel: 'internal',
            redirectRoute: 'dashboard',
        );
    }

    public function portalStore(Request $request, string $rateableType, int $rateableId = 0): RedirectResponse
    {
        return $this->storeForRater(
            request: $request,
            rateableType: $rateableType,
            rateableId: $rateableId,
            rater: auth('portal')->user(),
            channel: 'portal',
            redirectRoute: 'portal.home',
        );
    }

    public function rate(Request $request, string $rateableType, int $rateableId = 0): RedirectResponse
    {
        return $this->store($request, $rateableType, $rateableId);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, string $rateableType, int $rateableId = 0): Response
    {
        return $this->renderFormForRater(
            rateableType: $rateableType,
            rateableId: $rateableId,
            rater: $request->user(),
            channel: 'internal',
            storeRoute: 'rating.store',
            returnRoute: 'dashboard',
        );
    }

    public function portalCreate(string $rateableType, int $rateableId = 0): Response
    {
        return $this->renderFormForRater(
            rateableType: $rateableType,
            rateableId: $rateableId,
            rater: auth('portal')->user(),
            channel: 'portal',
            storeRoute: 'portal.rating.store',
            returnRoute: 'portal.home',
        );
    }

    private function storeForRater(Request $request, string $rateableType, int $rateableId, ?Model $rater, string $channel, string $redirectRoute): RedirectResponse
    {
        abort_if(! $rater, 403);

        $this->validateRateable($rateableType, $rateableId);

        $criteria = $this->criteriaFor($rateableType);

        $validated = $request->validate([
            'criteria' => 'required|array',
            'criteria.*' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        $criteriaIds = $criteria->pluck('id')->map(fn (int $id) => (string) $id);
        $submittedCriteriaIds = collect(array_keys($validated['criteria']));
        $invalidCriteria = $submittedCriteriaIds->diff($criteriaIds);
        $missingCriteria = $criteriaIds->diff($submittedCriteriaIds);

        if ($invalidCriteria->isNotEmpty() || $missingCriteria->isNotEmpty()) {
            throw ValidationException::withMessages([
                'criteria' => __('gestlab.rating.criteria_mismatch'),
            ]);
        }

        $existingRating = Rating::query()
            ->where('rateable_id', $rateableId)
            ->where('rateable_type', $rateableType)
            ->where(function ($query) use ($rater) {
                $query
                    ->where(function ($query) use ($rater) {
                        $query
                            ->where('rater_type', $rater->getMorphClass())
                            ->where('rater_id', $rater->getKey());
                    })
                    ->when($rater instanceof User, function ($query) use ($rater) {
                        $query->orWhere('user_id', $rater->id);
                    });
            })
            ->exists();

        if ($existingRating) {
            throw ValidationException::withMessages([
                'criteria' => __('gestlab.rating.already_rated'),
            ]);
        }

        $rating = DB::transaction(function () use ($channel, $criteria, $rateableId, $rateableType, $rater, $validated): Rating {
            $ratingData = $criteria
                ->mapWithKeys(fn (CriteriaRating $criterion) => [
                    $criterion->name => (int) $validated['criteria'][$criterion->id],
                ])
                ->all();

            $rating = Rating::create([
                'user_id' => $rater instanceof User ? $rater->id : null,
                'rateable_type' => $rateableType,
                'rateable_id' => $rateableId,
                'rater_type' => $rater->getMorphClass(),
                'rater_id' => $rater->getKey(),
                'channel' => $channel,
                'criteria' => $ratingData,
                'review' => $validated['review'] ?? null,
                'metadata' => [
                    'submitted_via' => $channel,
                ],
            ]);

            RatingRequest::query()
                ->where('rateable_type', $rateableType)
                ->where('rateable_id', $rateableId)
                ->where(function ($query) use ($rater) {
                    $query
                        ->where(function ($query) use ($rater) {
                            $query
                                ->where('rater_type', $rater->getMorphClass())
                                ->where('rater_id', $rater->getKey());
                        })
                        ->when($rater instanceof User, function ($query) use ($rater) {
                            $query->orWhere('user_id', $rater->id);
                        });
                })
                ->update(['status' => 'completed']);

            return $rating;
        });

        app(QualityModuleNotifier::class)->notifyRatingSubmitted($rating);

        return redirect()->route($redirectRoute)->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => __('gestlab.rating.thank_you'),
            ],
        ]);
    }

    private function renderFormForRater(string $rateableType, int $rateableId, ?Model $rater, string $channel, string $storeRoute, string $returnRoute): Response
    {
        abort_if(! $rater, 403);

        $rateableModel = $this->validateRateable($rateableType, $rateableId);

        return Inertia::render('RateForm', [
            'criteria' => $this->criteriaFor($rateableType),
            'rateableType' => $rateableType,
            'rateableId' => $rateableId,
            'rateableLabel' => $this->rateableLabel($rateableType, $rateableModel),
            'channel' => $channel,
            'storeRoute' => $storeRoute,
            'returnRoute' => $returnRoute,
            'ratingRequest' => RatingRequest::query()
                ->where('rateable_type', $rateableType)
                ->where('rateable_id', $rateableId)
                ->where(function ($query) use ($rater) {
                    $query
                        ->where(function ($query) use ($rater) {
                            $query
                                ->where('rater_type', $rater->getMorphClass())
                                ->where('rater_id', $rater->getKey());
                        })
                        ->when($rater instanceof User, function ($query) use ($rater) {
                            $query->orWhere('user_id', $rater->id);
                        });
                })
                ->first(),
        ]);
    }

    private function criteriaFor(string $rateableType)
    {
        $criteria = CriteriaRating::query()
            ->where('type', $rateableType)
            ->orderBy('id')
            ->get();

        if ($criteria->isEmpty() && $rateableType !== 'service') {
            $criteria = CriteriaRating::query()
                ->where('type', 'service')
                ->orderBy('id')
                ->get();
        }

        abort_if($criteria->isEmpty(), 404, __('gestlab.rating.no_criteria'));

        return $criteria;
    }

    private function validateRateable(string $type, int $id): ?Model
    {
        $rateableModel = $this->getRateableModel($type, $id);

        abort_if($type !== 'service' && ! $rateableModel, 404, __('gestlab.rating.invalid_rateable'));

        return $rateableModel;
    }

    private function getRateableModel(string $type, int $id): ?Model
    {
        return match ($type) {
            'order' => InventoryOrder::query()->find($id),
            'proposal' => VAPProposal::query()->find($id),
            'sample_entry' => VAPSampleEntry::query()->find($id),
            'customer_request' => CustomerRequest::query()->find($id),
            'quality_certificate' => QualityCertificate::query()->find($id),
            'maintenance_task' => MaintenanceTask::query()->find($id),
            'paid_service' => PaidService::query()->find($id),
            'service' => null,
            default => null,
        };
    }

    private function rateableLabel(string $type, ?Model $model): string
    {
        if (! $model) {
            return __('gestlab.rating.subjects.service');
        }

        return match ($type) {
            'order' => $model->reference ?? __('gestlab.rating.subjects.order'),
            'proposal' => $model->proposal_number ?? $model->proposal_no ?? __('gestlab.rating.subjects.proposal'),
            'sample_entry' => $model->code ?? $model->name ?? __('gestlab.rating.subjects.sample_entry'),
            'customer_request' => $model->reference ?? $model->title ?? __('gestlab.rating.subjects.customer_request'),
            'quality_certificate' => $model->code ?? __('gestlab.rating.subjects.quality_certificate'),
            'maintenance_task' => $model->maintenance_task_no ?? $model->name ?? __('gestlab.rating.subjects.maintenance_task'),
            'paid_service' => $model->name ?? __('gestlab.rating.subjects.paid_service'),
            default => __('gestlab.rating.subjects.service'),
        };
    }

    private function ratingStats(): array
    {
        $ratings = Rating::query()
            ->latest()
            ->limit(500)
            ->get(['criteria', 'channel', 'created_at']);

        $scores = $ratings
            ->flatMap(fn (Rating $rating) => collect($rating->criteria ?? [])->values())
            ->map(fn ($score) => (int) $score)
            ->filter(fn (int $score) => $score > 0);

        return [
            'total' => Rating::query()->count(),
            'portal' => Rating::query()->where('channel', 'portal')->count(),
            'internal' => Rating::query()->where('channel', 'internal')->count(),
            'average' => $scores->isEmpty() ? 0 : round($scores->average(), 2),
        ];
    }

    private function ratingCharts(): array
    {
        return [
            'by_type' => $this->ratingDistributionChart('rateable_type'),
            'by_channel' => $this->ratingDistributionChart('channel', [
                'internal' => 'Interno',
                'portal' => 'Portal',
            ]),
            'monthly' => $this->ratingMonthlyTrendChart(),
            'score_distribution' => $this->ratingScoreDistributionChart(),
        ];
    }

    private function ratingDistributionChart(string $column, ?array $labels = null): array
    {
        $distribution = Rating::query()
            ->selectRaw("{$column}, count(*) as aggregate")
            ->groupBy($column)
            ->pluck('aggregate', $column);

        $items = $labels
            ? collect($labels)->map(fn (string $label, string $key) => [
                'label' => $label,
                'value' => (int) ($distribution[$key] ?? 0),
            ])
            : $distribution->map(fn ($value, $key) => [
                'label' => (string) $key,
                'value' => (int) $value,
            ])->values();

        return [
            'labels' => $items->pluck('label')->values()->all(),
            'series' => $items->pluck('value')->values()->all(),
        ];
    }

    private function ratingMonthlyTrendChart(): array
    {
        $months = collect(range(5, 0))->map(fn (int $monthsAgo) => now()->startOfMonth()->subMonths($monthsAgo));
        $firstMonth = $months->first()->copy();
        $lastMonth = $months->last()->copy()->endOfMonth();

        $aggregates = Rating::query()
            ->whereBetween('created_at', [$firstMonth, $lastMonth])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, count(*) as aggregate")
            ->groupBy('month_key')
            ->pluck('aggregate', 'month_key');

        return [
            'categories' => $months->map(fn ($month) => $month->translatedFormat('M Y'))->values()->all(),
            'series' => [
                [
                    'name' => 'Avaliações',
                    'data' => $months->map(fn ($month) => (int) ($aggregates[$month->format('Y-m')] ?? 0))->values()->all(),
                ],
            ],
        ];
    }

    private function ratingScoreDistributionChart(): array
    {
        $scores = Rating::query()
            ->latest()
            ->limit(500)
            ->get(['criteria'])
            ->flatMap(fn (Rating $rating) => collect($rating->criteria ?? [])->values())
            ->map(fn ($score) => (int) $score)
            ->filter(fn (int $score) => $score >= 1 && $score <= 5)
            ->countBy();

        $labels = [1, 2, 3, 4, 5];

        return [
            'labels' => collect($labels)->map(fn (int $score) => (string) $score)->all(),
            'series' => collect($labels)->map(fn (int $score) => (int) ($scores[$score] ?? 0))->all(),
        ];
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
    }
}
