<?php

namespace App\Services;

use App\Models\CriteriaRating;
use App\Models\InventoryOrder;
use App\Models\MaintenanceTask;
use App\Models\PaidService;
use App\Models\RatingRequest;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPProposal;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class RatingLaboratoryAccess
{
    public const INTERNAL_TYPES = ['service', 'order', 'proposal', 'sample_entry', 'maintenance_task', 'paid_service'];

    public const PORTAL_TYPES = ['service', 'sample_entry', 'proposal'];

    public function __construct(private readonly LaboratoryWorkflowOwnership $ownership) {}

    public function member(int $labId, int $userId, bool $lock = false): User
    {
        $lab = VAPLab::query()->when($lock, fn ($query) => $query->lockForUpdate())->find($labId);
        $membership = DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $userId)
            ->when($lock, fn ($query) => $query->lockForUpdate())->first();
        $member = $this->ownership->eligibleUsers($labId)
            ->when($lock, fn ($query) => $query->lockForUpdate())->find($userId);
        abort_unless($membership && $member && $lab, 403);

        return $member;
    }

    public function recipient(int $id, bool $lock = false): Warehouse
    {
        return Warehouse::query()->whereNotNull('email_verified_at')->whereHas('customer')
            ->when($lock, fn ($query) => $query->lockForUpdate())->findOrFail($id);
    }

    public function subject(int $labId, string $type, int $id, ?Warehouse $recipient = null, bool $lock = false): ?Model
    {
        abort_unless(in_array($type, $recipient ? self::PORTAL_TYPES : self::INTERNAL_TYPES, true), 404);
        if ($type === 'service') {
            abort_unless($id === 0, 404);

            return null;
        }
        abort_unless($id > 0, 404);

        $query = match ($type) {
            'order' => InventoryOrder::forLaboratory($labId),
            'proposal' => VAPProposal::withoutGlobalScope('proposal_laboratory')->where('lab_id', $labId),
            'sample_entry' => VAPSampleEntry::query()->where('lab_id', $labId),
            'maintenance_task' => MaintenanceTask::forLaboratory($labId),
            'paid_service' => PaidService::query(),
        };
        $subject = $query->when($lock, fn ($query) => $query->lockForUpdate())->findOrFail($id);

        if ($recipient) {
            abort_unless((int) $subject->customer_id === (int) $recipient->customer_id
                && (int) $subject->warehouse_id === (int) $recipient->id, 404);
        }

        return $subject;
    }

    /** @return list<array{id: int, name: string, description: ?string}> */
    public function criteria(string $type): array
    {
        $criteria = CriteriaRating::query()->where('type', $type)->orderBy('id')->get(['id', 'name', 'description']);
        if ($criteria->isEmpty() && $type !== 'service') {
            $criteria = CriteriaRating::query()->where('type', 'service')->orderBy('id')->get(['id', 'name', 'description']);
        }
        abort_if($criteria->isEmpty(), 404, __('gestlab.rating.no_criteria'));

        return $criteria->map(fn (CriteriaRating $criterion): array => [
            'id' => (int) $criterion->id, 'name' => $criterion->name, 'description' => $criterion->description,
        ])->all();
    }

    public function invitation(string $uuid, int $recipientId, bool $lock = false): RatingRequest
    {
        $recipient = $this->recipient($recipientId, $lock);
        $invitation = RatingRequest::query()->where('invitation', $uuid)->where('channel', 'portal')
            ->where('rater_type', $recipient->getMorphClass())->where('rater_id', $recipient->id)
            ->where('recipient_customer_id', $recipient->customer_id)
            ->where('status', 'pending')->where('expires_at', '>', now())->whereHas('lab')
            ->when($lock, fn ($query) => $query->lockForUpdate())->firstOrFail();
        $this->subject((int) $invitation->lab_id, $invitation->rateable_type, (int) $invitation->rateable_id, $recipient, $lock);

        return $invitation;
    }

    /** @return Builder<RatingRequest> */
    public function pendingInvitations(int $recipientId): Builder
    {
        $recipient = $this->recipient($recipientId);

        return RatingRequest::query()->where('channel', 'portal')->where('status', 'pending')
            ->where('rater_type', $recipient->getMorphClass())->where('rater_id', $recipient->id)
            ->where('recipient_customer_id', $recipient->customer_id)->where('expires_at', '>', now())->whereHas('lab')
            ->where(function (Builder $query) use ($recipient): void {
                $query->where(fn (Builder $service): Builder => $service->where('rateable_type', 'service')->where('rateable_id', 0))
                    ->orWhere(function (Builder $sample) use ($recipient): void {
                        $sample->where('rateable_type', 'sample_entry')->whereExists(function (QueryBuilder $entry) use ($recipient): void {
                            $entry->selectRaw('1')->from('sample_entries')->whereColumn('sample_entries.id', 'rating_requests.rateable_id')
                                ->whereColumn('sample_entries.lab_id', 'rating_requests.lab_id')->whereNull('sample_entries.deleted_at')
                                ->where('sample_entries.warehouse_id', $recipient->id)->where('sample_entries.customer_id', $recipient->customer_id);
                        });
                    })->orWhere(function (Builder $proposal) use ($recipient): void {
                        $proposal->where('rateable_type', 'proposal')->whereExists(function (QueryBuilder $entry) use ($recipient): void {
                            $entry->selectRaw('1')->from('proposals')->whereColumn('proposals.id', 'rating_requests.rateable_id')
                                ->whereColumn('proposals.lab_id', 'rating_requests.lab_id')->whereNull('proposals.deleted_at')
                                ->where('proposals.warehouse_id', $recipient->id)->where('proposals.customer_id', $recipient->customer_id);
                        });
                    });
            });
    }

    public function label(string $type, ?Model $subject): string
    {
        return $subject?->reference ?? $subject?->proposal_no ?? $subject?->code
            ?? $subject?->maintenance_task_no ?? $subject?->name ?? __('gestlab.rating.subjects.'.$type);
    }
}
