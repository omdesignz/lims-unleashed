<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\VAPLab;
use App\Models\Warehouse;
use App\Services\LaboratoryWorkflowOwnership;
use App\Services\ProposalNotificationOwnership;
use App\Services\RatingLaboratoryAccess;
use App\Services\SharedDocumentDeliveryAccess;
use App\Support\WhiteLabelMessageDefaults;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OperationalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public readonly array $payload)
    {
        $this->afterCommit();
        $this->onQueue('notifications');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = $this->payload['channels'] ?? ['database', 'broadcast'];

        if (str_starts_with((string) ($this->payload['key'] ?? ''), 'lab.')
            || array_key_exists('lab_id', $this->payload['context'] ?? [])) {
            $channels = array_map(fn (string $channel): string => $channel === 'broadcast' ? LaboratoryBroadcastChannel::class : $channel, $channels);
        }

        return $channels;
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        $context = $this->payload['context'] ?? [];
        if (array_key_exists('lab_id', $context)) {
            $labId = filter_var($context['lab_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (! $labId || ! VAPLab::query()->whereKey($labId)->exists()) {
                return false;
            }
            if ($notifiable instanceof User
                && ! app(LaboratoryWorkflowOwnership::class)->eligibleUsers($labId)->whereKey($notifiable->getKey())->exists()) {
                return false;
            }
        }

        $key = (string) ($this->payload['key'] ?? '');
        if (in_array($key, ['documents.shared', 'documents.share_failed', 'documents.share_uncertain'], true)) {
            return $notifiable instanceof User && app(SharedDocumentDeliveryAccess::class)->canReceive($notifiable, $context);
        }
        if (str_starts_with($key, 'commercial.proposal.')) {
            $ownership = app(ProposalNotificationOwnership::class);
            $proposal = $ownership->resolve($context);

            return $proposal && $ownership->canReceive($notifiable, $proposal);
        }
        if ($key === 'quality.rating.requested') {
            if (! $notifiable instanceof Warehouse) {
                return false;
            }
            try {
                return app(RatingLaboratoryAccess::class)->pendingInvitations((int) $notifiable->getKey())
                    ->where('invitation', data_get($this->payload, 'context.invitation'))->exists();
            } catch (ModelNotFoundException) {
                return false;
            }
        }
        if ($key === 'quality.rating.received') {
            $labId = filter_var(data_get($this->payload, 'context.lab_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            if (! $labId || ! $notifiable instanceof User) {
                return false;
            }

            return app(LaboratoryWorkflowOwnership::class)->eligibleUsers($labId)->find($notifiable->getKey())?->can('view_ratings') ?? false;
        }
        if (str_starts_with($key, 'quality.nonconformity.')) {
            $labId = filter_var(data_get($this->payload, 'context.lab_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            return $labId && $notifiable instanceof User
                && (app(LaboratoryWorkflowOwnership::class)->eligibleUsers($labId)->find($notifiable->getKey())?->can('view_occurrences') ?? false);
        }
        if (! str_starts_with((string) ($this->payload['key'] ?? ''), 'lab.')) {
            return true;
        }

        $ownership = app(LaboratoryWorkflowOwnership::class);
        $entry = $ownership->resolve($this->payload['context'] ?? []);

        return $entry && $ownership->canReceive($notifiable, $entry);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $defaults = WhiteLabelMessageDefaults::current();
        $mail = (new MailMessage)
            ->subject($this->payload['email_subject'] ?? $this->payload['title'])
            ->greeting($defaults->mailGreeting())
            ->line($this->payload['email_message'] ?? $this->payload['message']);

        if (filled($this->payload['action_url'] ?? null)) {
            $mail->action($this->payload['action_label'] ?: 'Abrir plataforma', $this->payload['action_url']);
        }

        return $mail
            ->line($defaults->notificationEmailOutro())
            ->salutation($defaults->salutationWithSignature());
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->databasePayload();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage($this->databasePayload()))->onQueue('broadcasts');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->databasePayload();
    }

    /**
     * @return array<string, mixed>
     */
    private function databasePayload(): array
    {
        return collect($this->payload)->only([
            'key', 'category', 'priority', 'title', 'message', 'action_label', 'action_url', 'sender_id', 'sender_name', 'context',
        ])->all();
    }
}
