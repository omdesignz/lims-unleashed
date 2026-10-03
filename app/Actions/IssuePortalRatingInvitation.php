<?php

namespace App\Actions;

use App\Http\Requests\IssuePortalRatingInvitationRequest;
use App\Models\RatingRequest;
use App\Models\Warehouse;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\RatingLaboratoryAccess;
use App\Support\NotificationTemplateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IssuePortalRatingInvitation
{
    public function __construct(
        private readonly LaboratoryWorkflowMutationAccess $mutationAccess,
        private readonly RatingLaboratoryAccess $access,
        private readonly NotificationTemplateService $notifications,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(int $labId, int $userId, array $data): RatingRequest
    {
        return DB::transaction(function () use ($labId, $userId, $data): RatingRequest {
            $operator = $this->mutationAccess->operator($userId, $labId, 'add_ratings');
            $validated = Validator::make($data, (new IssuePortalRatingInvitationRequest)->rules())->validate();
            $recipient = Warehouse::query()->where('email', $validated['recipient_email'])
                ->whereNotNull('email_verified_at')->whereHas('customer')->lockForUpdate()->first();
            if (! $recipient) {
                throw ValidationException::withMessages(['recipient_email' => 'Seleccione uma conta do portal verificada e com cliente activo.']);
            }
            $type = $validated['rateable_type'];
            $id = (int) $validated['rateable_id'];
            $this->access->subject($labId, $type, $id, $recipient, true);
            $identity = [
                'lab_id' => $labId, 'rateable_type' => $type, 'rateable_id' => $id,
                'rater_type' => $recipient->getMorphClass(), 'rater_id' => $recipient->id, 'channel' => 'portal',
            ];
            $existing = RatingRequest::query()->where($identity)->where('status', 'pending')->lockForUpdate()->first();
            if ($existing) {
                if ((int) $existing->recipient_customer_id === (int) $recipient->customer_id && $existing->expires_at->isFuture()) {
                    return $existing;
                }
                $existing->update(['status' => 'expired']);
            }
            $invitation = RatingRequest::query()->create([
                ...$identity, 'issued_by_id' => $operator->id, 'invitation' => (string) Str::uuid(),
                'recipient_customer_id' => $recipient->customer_id,
                'criteria_snapshot' => $this->access->criteria($type), 'expires_at' => now()->addDays(30), 'status' => 'pending',
            ]);
            $this->notifications->notify([$recipient], 'quality.rating.requested', [
                'lab_id' => $labId, 'lab_name' => $invitation->lab?->name,
                'rateable_type' => $type, 'rateable_id' => $id,
                'invitation' => $invitation->invitation,
                'document_url' => route('portal.rating.create', ['invitation' => $invitation->invitation]),
            ]);

            return $invitation;
        }, 3);
    }
}
