<?php

namespace App\Actions;

use App\Http\Requests\SubmitRatingRequest;
use App\Models\Rating;
use App\Models\RatingRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\RatingLaboratoryAccess;
use App\Support\QualityModuleNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SubmitRating
{
    public function __construct(private readonly RatingLaboratoryAccess $access, private readonly QualityModuleNotifier $notifier) {}

    /** @param array<string, mixed> $data */
    public function internal(int $labId, int $userId, string $type, int $id, array $data): Rating
    {
        return DB::transaction(function () use ($labId, $userId, $type, $id, $data): Rating {
            $operator = $this->access->member($labId, $userId, true);
            $this->access->subject($labId, $type, $id, lock: true);

            return $this->persist($labId, $type, $id, $operator, 'internal', $this->access->criteria($type), $data);
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function portal(string $uuid, int $recipientId, array $data): Rating
    {
        return DB::transaction(function () use ($uuid, $recipientId, $data): Rating {
            $invitation = $this->access->invitation($uuid, $recipientId, true);
            $rating = $this->persist((int) $invitation->lab_id, $invitation->rateable_type, (int) $invitation->rateable_id,
                $this->access->recipient($recipientId, true), 'portal', $invitation->criteria_snapshot, $data, $invitation);
            $invitation->update(['status' => 'completed']);

            return $rating;
        }, 3);
    }

    /**
     * @param  list<array{id: int, name: string, description: ?string}>  $criteria
     * @param  array<string, mixed>  $data
     */
    private function persist(int $labId, string $type, int $id, User|Warehouse $rater, string $channel, array $criteria, array $data, ?RatingRequest $invitation = null): Rating
    {
        $validated = Validator::make($data, (new SubmitRatingRequest)->rules())->validate();
        $expected = collect($criteria)->pluck('id')->map(fn ($id): string => (string) $id)->sort()->values()->all();
        $submitted = collect(array_keys($validated['criteria']))->map(fn ($id): string => (string) $id)->sort()->values()->all();
        if ($expected === [] || $expected !== $submitted) {
            throw ValidationException::withMessages(['criteria' => __('gestlab.rating.criteria_mismatch')]);
        }
        $identity = [
            'lab_id' => $labId, 'rateable_type' => $type, 'rateable_id' => $id,
            'rater_type' => $rater->getMorphClass(), 'rater_id' => $rater->id, 'channel' => $channel,
        ];
        if ($channel === 'internal' && Rating::withTrashed()->where($identity)->exists()) {
            throw ValidationException::withMessages(['criteria' => __('gestlab.rating.already_rated')]);
        }
        $rating = Rating::query()->create([
            ...$identity, 'user_id' => $rater instanceof User ? $rater->id : null, 'rating_request_id' => $invitation?->id,
            'criteria' => collect($criteria)->mapWithKeys(fn (array $criterion): array => [(string) $criterion['id'] => (int) $validated['criteria'][$criterion['id']]])->all(),
            'review' => $validated['review'] ?? null, 'metadata' => ['submitted_via' => $channel, 'criteria_snapshot' => $criteria],
        ]);
        if ($channel === 'internal') {
            RatingRequest::query()->where($identity)->where('status', 'pending')->update(['status' => 'completed']);
        }
        $this->notifier->notifyRatingSubmitted($rating);

        return $rating;
    }
}
