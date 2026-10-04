<?php

namespace App\Actions;

use App\Http\Requests\StoreAdminNotificationRequest;
use App\Models\BroadcastNotification;
use App\Models\User;
use App\Models\VAPLab;
use App\Notifications\GlobalNotification;
use App\Services\LaboratoryWorkflowOwnership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class IssueLaboratoryNotification
{
    public function __construct(private readonly LaboratoryWorkflowOwnership $ownership) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{notification: BroadcastNotification, dispatch_failed: bool}
     */
    public function execute(int $userId, int $labId, array $data): array
    {
        $dispatchFailed = false;
        $notification = DB::transaction(function () use ($userId, $labId, $data, &$dispatchFailed): BroadcastNotification {
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            $lab = VAPLab::query()->lockForUpdate()->find($labId);
            if (! $lab) {
                throw new AuthorizationException;
            }
            $memberIds = DB::table('lab_user')->where('lab_id', $labId)->orderBy('user_id')->lockForUpdate()->pluck('user_id');
            User::query()->whereIn('id', $memberIds)->orderBy('id')->lockForUpdate()->get();
            $sender = $this->sender($userId, $labId);
            $validated = Validator::make($data, (new StoreAdminNotificationRequest)->rulesForLaboratory($labId))->validate();
            if ($validated['schedule_send'] ?? false) {
                throw ValidationException::withMessages(['scheduled_at' => 'As notificações agendadas ainda não estão disponíveis. Envie esta notificação imediatamente.']);
            }
            $recipients = $this->audience($labId, $validated)->orderBy('id')->lockForUpdate()->get();
            if ($recipients->isEmpty() || ($validated['recipient_type'] === 'specific' && $recipients->count() !== count($validated['recipients']))) {
                throw ValidationException::withMessages(['recipients' => 'Confirme os destinatários elegíveis deste laboratório.']);
            }
            $recipientIds = $recipients->modelKeys();
            $intent = [
                'lab_id' => $labId, 'sender_id' => $sender->id,
                'title' => $validated['title'], 'message' => $validated['message'],
                'type' => $validated['type'], 'priority' => $validated['priority'],
                'recipient_type' => $validated['recipient_type'], 'recipient_count' => count($recipientIds),
                'scheduled_at' => null, 'expires_at' => null,
            ];
            $notification = new BroadcastNotification($intent);
            abort_unless($notification->save(), 409, 'Não foi possível registar a emissão da notificação.');
            $persisted = $notification->fresh();
            abort_unless($persisted && $persisted->only(array_keys($intent)) === $intent, 409,
                'Não foi possível confirmar a emissão registada.');
            $this->sender($userId, $labId);
            if ($this->audience($labId, $validated)->whereKey($recipientIds)->count() !== count($recipientIds)) {
                throw ValidationException::withMessages(['recipients' => 'Os destinatários mudaram durante a emissão. Reveja a selecção.']);
            }

            DB::afterCommit(function () use ($persisted, $intent, $recipientIds, $userId, $labId, &$dispatchFailed): void {
                try {
                    $sender = $this->sender($userId, $labId);
                    $current = $persisted->fresh();
                    abort_unless($current && $current->only(array_keys($intent)) === $intent, 409);
                    foreach ($this->ownership->eligibleUsers($labId)->whereKey($recipientIds)->orderBy('id')->get() as $recipient) {
                        $recipient->notify(new GlobalNotification(
                            $intent['title'], $intent['message'], $sender, $intent['type'], $intent['priority'], labId: $labId,
                        ));
                    }
                } catch (Throwable $exception) {
                    $dispatchFailed = true;
                    report($exception);
                }
            });

            return $persisted;
        }, 3);

        return ['notification' => $notification, 'dispatch_failed' => $dispatchFailed];
    }

    private function sender(int $userId, int $labId): User
    {
        $sender = $this->ownership->eligibleUsers($labId)->whereKey($userId)->first();
        if (! VAPLab::query()->whereKey($labId)->exists() || ! $sender?->hasRole('admin')) {
            throw new AuthorizationException('Sem autorização actual para emitir notificações neste laboratório.');
        }

        return $sender;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Builder<User>
     */
    private function audience(int $labId, array $data): Builder
    {
        $query = $this->ownership->eligibleUsers($labId);
        if ($data['recipient_type'] === 'specific') {
            return $query->whereKey($data['recipients']);
        }
        if ($data['recipient_type'] === 'group') {
            return match ($data['group']) {
                'active' => $query->where('last_login_at', '>=', now()->subMonth()),
                'new' => $query->where('created_at', '>=', now()->subWeek()),
                'admins' => $query->role('admin'),
                default => $query,
            };
        }

        return $query;
    }
}
