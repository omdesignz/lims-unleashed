<?php

namespace App\Http\Controllers;

use App\Actions\IssuePortalRatingInvitation;
use App\Actions\SubmitRating;
use App\Http\Requests\IssuePortalRatingInvitationRequest;
use App\Http\Requests\SubmitRatingRequest;
use App\Http\Resources\RatingResource;
use App\Models\Rating;
use App\Models\RatingRequest;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\RatingLaboratoryAccess;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RatingController extends Controller
{
    public function __construct(
        private readonly SampleLaboratoryAccess $laboratoryAccess,
        private readonly RatingLaboratoryAccess $access,
        private readonly LaboratoryWorkflowMutationAccess $mutationAccess,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('view_ratings'), 403);
        $labId = $this->laboratoryAccess->activeLabId();
        $this->access->member($labId, (int) $request->user()->id);
        $summary = $this->scoreSummary($labId);

        return Inertia::render('Ratings/Index', [
            'ratings' => RatingResource::collection(Rating::query()->where('lab_id', $labId)->latest()->paginate(20)),
            'stats' => [
                'total' => Rating::query()->where('lab_id', $labId)->count(),
                'portal' => Rating::query()->where('lab_id', $labId)->where('channel', 'portal')->count(),
                'internal' => Rating::query()->where('lab_id', $labId)->where('channel', 'internal')->count(),
                'average' => $summary['count'] ? round($summary['sum'] / $summary['count'], 2) : 0,
            ],
            'charts' => $this->ratingCharts($summary['distribution']),
            'canInvite' => $request->user()->can('add_ratings'),
            'invitations' => RatingRequest::query()->where('lab_id', $labId)->where('channel', 'portal')
                ->with('rater:id,name')->latest()->limit(20)->get()
                ->map(fn (RatingRequest $invitation): array => [
                    'invitation' => $invitation->invitation, 'recipient' => $invitation->rater?->name,
                    'rateable_type' => $invitation->rateable_type, 'rateable_id' => $invitation->rateable_id,
                    'status' => $invitation->status === 'pending' && $invitation->expires_at->isPast() ? 'expired' : $invitation->status,
                    'expires_at' => $invitation->expires_at,
                ]),
        ]);
    }

    public function store(SubmitRatingRequest $request, SubmitRating $submit, string $rateableType, string $rateableId = '0'): RedirectResponse
    {
        $submit->internal($this->laboratoryAccess->activeLabId(), (int) $request->user()->id, $rateableType, $this->subjectId($rateableId), $request->validated());

        return $this->thanks('dashboard');
    }

    public function portalStore(SubmitRatingRequest $request, SubmitRating $submit, string $invitation): RedirectResponse
    {
        $submit->portal($invitation, (int) $request->user('portal')->id, $request->validated());

        return $this->thanks('portal.home');
    }

    public function create(Request $request, string $rateableType, string $rateableId = '0'): Response
    {
        $labId = $this->laboratoryAccess->activeLabId();
        $this->access->member($labId, (int) $request->user()->id);
        $id = $this->subjectId($rateableId);
        $subject = $this->access->subject($labId, $rateableType, $id);
        $pending = RatingRequest::query()->where('lab_id', $labId)->where('channel', 'internal')
            ->where('rateable_type', $rateableType)->where('rateable_id', $id)
            ->where('rater_type', $request->user()->getMorphClass())->where('rater_id', $request->user()->id)->first();

        return Inertia::render('RateForm', [
            'criteria' => $this->access->criteria($rateableType), 'rateableType' => $rateableType, 'rateableId' => $id,
            'rateableLabel' => $this->access->label($rateableType, $subject),
            'storeRoute' => 'rating.store', 'storeParameters' => ['rateableType' => $rateableType, 'rateableId' => $id],
            'returnRoute' => 'dashboard', 'ratingRequest' => $pending ? ['status' => $pending->status] : null,
        ]);
    }

    public function portalCreate(Request $request, string $invitation): Response
    {
        $pending = $this->access->invitation($invitation, (int) $request->user('portal')->id);
        $recipient = $this->access->recipient((int) $request->user('portal')->id);
        $subject = $this->access->subject((int) $pending->lab_id, $pending->rateable_type, (int) $pending->rateable_id, $recipient);

        return Inertia::render('RateForm', [
            'criteria' => $pending->criteria_snapshot, 'rateableType' => $pending->rateable_type, 'rateableId' => (int) $pending->rateable_id,
            'rateableLabel' => $this->access->label($pending->rateable_type, $subject), 'laboratoryName' => $pending->lab?->name,
            'storeRoute' => 'portal.rating.store', 'storeParameters' => ['invitation' => $pending->invitation],
            'returnRoute' => 'portal.home', 'ratingRequest' => ['status' => $pending->status, 'expires_at' => $pending->expires_at],
        ]);
    }

    public function portalIndex(Request $request): Response
    {
        $invitations = $this->access->pendingInvitations((int) $request->user('portal')->id)->with('lab:id,name')->latest()->paginate(20);
        $invitations->through(fn (RatingRequest $invitation): array => [
            'invitation' => $invitation->invitation, 'laboratory' => $invitation->lab->name,
            'rateable_type' => $invitation->rateable_type, 'rateable_id' => (int) $invitation->rateable_id,
            'expires_at' => $invitation->expires_at,
        ]);

        return Inertia::render('ClientPortal/Ratings/Index', ['invitations' => $invitations]);
    }

    public function issueInvitation(IssuePortalRatingInvitationRequest $request, IssuePortalRatingInvitation $issue): RedirectResponse
    {
        $issue->execute($this->laboratoryAccess->activeLabId(), (int) $request->user()->id, $request->validated());

        return redirect()->route('ratings.index')->with('toast', ['title' => 'Convite registado', 'message' => 'Convite do laboratório disponível no portal. Validade: 30 dias.']);
    }

    public function revokeInvitation(Request $request, string $invitation): RedirectResponse
    {
        $labId = $this->laboratoryAccess->activeLabId();
        DB::transaction(function () use ($request, $labId, $invitation): void {
            $this->mutationAccess->operator((int) $request->user()->id, $labId, 'add_ratings');
            $pending = RatingRequest::query()->where('lab_id', $labId)->where('channel', 'portal')
                ->where('invitation', $invitation)->lockForUpdate()->firstOrFail();
            abort_if($pending->status === 'completed', 409);
            $pending->update(['status' => 'revoked']);
        }, 3);

        return redirect()->route('ratings.index');
    }

    private function subjectId(string $value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        abort_if($id === false, 404);

        return $id;
    }

    private function thanks(string $route): RedirectResponse
    {
        return redirect()->route($route)->with('toast', ['title' => trans('gestlab.toasts.notification'), 'message' => __('gestlab.rating.thank_you')]);
    }

    /** @return array{sum: int, count: int, distribution: array<int, int>} */
    private function scoreSummary(int $labId): array
    {
        $summary = ['sum' => 0, 'count' => 0, 'distribution' => []];
        foreach (Rating::query()->where('lab_id', $labId)->select(['id', 'criteria'])->lazyById(200) as $rating) {
            foreach ($rating->criteria ?? [] as $score) {
                if (is_int($score) && $score >= 1 && $score <= 5) {
                    $summary['sum'] += $score;
                    $summary['count']++;
                    $summary['distribution'][$score] = ($summary['distribution'][$score] ?? 0) + 1;
                }
            }
        }

        return $summary;
    }

    private function ratingCharts(array $scores): array
    {
        return [
            'by_type' => $this->ratingDistributionChart('rateable_type'),
            'by_channel' => $this->ratingDistributionChart('channel', [
                'internal' => 'Interno',
                'portal' => 'Portal',
            ]),
            'monthly' => $this->ratingMonthlyTrendChart(),
            'score_distribution' => $this->ratingScoreDistributionChart($scores),
        ];
    }

    private function ratingDistributionChart(string $column, ?array $labels = null): array
    {
        $distribution = Rating::query()->where('lab_id', $this->laboratoryAccess->activeLabId())
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

        $aggregates = Rating::query()->where('lab_id', $this->laboratoryAccess->activeLabId())
            ->whereBetween('created_at', [$firstMonth, $lastMonth])
            ->selectRaw("to_char(created_at, 'YYYY-MM') as month_key, count(*) as aggregate")
            ->groupByRaw("to_char(created_at, 'YYYY-MM')")
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

    /** @param array<int, int> $scores */
    private function ratingScoreDistributionChart(array $scores): array
    {
        $labels = [1, 2, 3, 4, 5];

        return [
            'labels' => array_map(fn (int $score): string => (string) $score, $labels),
            'series' => array_map(fn (int $score): int => $scores[$score] ?? 0, $labels),
        ];
    }
}
