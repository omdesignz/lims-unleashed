<?php

namespace App\Support;

use App\Models\NotificationTemplate;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use App\Notifications\OperationalNotification;
use App\Services\LaboratoryWorkflowOwnership;
use App\Services\ProposalNotificationOwnership;
use App\Settings\GeneralSettings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class NotificationTemplateService
{
    public function __construct(
        private readonly NotificationTemplateCatalog $catalog,
        private readonly GeneralSettings $settings,
        private readonly NotificationChannelResolver $channelResolver,
        private readonly LaboratoryWorkflowOwnership $ownership,
        private readonly ProposalNotificationOwnership $proposalOwnership
    ) {}

    /**
     * @param  array<string, scalar|null>  $context
     * @return array<string, mixed>|null
     */
    public function render(string $key, array $context = []): ?array
    {
        $definition = $this->catalog->definitions()[$key] ?? null;

        if (! $definition) {
            return null;
        }

        $labId = filter_var($context['lab_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (array_key_exists('lab_id', $context) && $labId === false) {
            return null;
        }

        $laboratory = $labId ? VAPLab::query()->find($labId) : null;
        if ($labId && ! $laboratory) {
            return null;
        }

        $template = $labId ? NotificationTemplate::query()->where('lab_id', $labId)->where('key', $key)->first() : null;
        $values = $template ? [...$definition, ...$template->only(NotificationTemplate::EDITABLE_FIELDS)] : $definition;

        if (! ($values['enabled'] ?? true)) {
            return null;
        }

        $context = [
            'lab_name' => $laboratory?->name ?: $this->settings->app_client_lab_name ?: $this->settings->app_name ?: 'LIMS Unleashed',
            'actor_name' => auth()->user()?->name ?? 'Sistema',
            ...$context,
        ];

        return [
            'key' => $key,
            'category' => $values['category'],
            'priority' => $values['priority'] ?? 'normal',
            'title' => $this->interpolate((string) $values['title_template'], $context),
            'message' => $this->interpolate((string) $values['in_app_template'], $context),
            'email_subject' => $this->interpolate((string) ($values['email_subject_template'] ?: $values['title_template']), $context),
            'email_message' => $this->interpolate((string) ($values['email_template'] ?: $values['in_app_template']), $context),
            'action_label' => $this->interpolate((string) ($values['action_label_template'] ?? ''), $context),
            'action_url' => $this->interpolate((string) ($values['action_url_template'] ?? ''), $context),
            'channels' => array_values(array_intersect($values['channels'] ?? ['database', 'broadcast'], ['database', 'broadcast', 'mail'])),
            'sender_id' => auth()->id(),
            'sender_name' => $this->settings->app_notification_sender_alias ?: $context['actor_name'],
            'context' => $context,
        ];
    }

    /**
     * @param  array<string, scalar|null>  $context
     */
    public function notifyPermission(string $key, array $context = [], ?int $excludeUserId = null): int
    {
        $definition = $this->catalog->definitions()[$key] ?? null;
        $permission = $definition['audience_permission'] ?? null;

        if (! $permission || ! Permission::query()->where('name', $permission)->where('guard_name', 'web')->exists()) {
            return 0;
        }

        $entry = str_starts_with($key, 'lab.') ? $this->ownership->resolve($context) : null;

        if (str_starts_with($key, 'lab.') && ! $entry) {
            return 0;
        }

        $hasLabContext = array_key_exists('lab_id', $context);
        $explicitLabId = filter_var($context['lab_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (($hasLabContext && $explicitLabId === false)
            || ($entry && $hasLabContext && $explicitLabId !== (int) $entry->lab_id)) {
            return 0;
        }

        $labId = $entry ? (int) $entry->lab_id : ($hasLabContext ? $explicitLabId : null);

        $users = ($labId ? $this->ownership->eligibleUsers($labId) : User::query())
            ->with('notificationPreferences')
            ->permission($permission)
            ->where('is_active', true)
            ->when($excludeUserId, fn ($query) => $query->whereKeyNot($excludeUserId))
            ->get();

        return $this->notify($users, $key, $context);
    }

    /**
     * @param  iterable<int, mixed>  $notifiables
     * @param  array<string, scalar|null>  $context
     */
    public function notify(iterable $notifiables, string $key, array $context = []): int
    {
        $isProposalNotification = str_starts_with($key, 'commercial.proposal.');
        $proposal = $isProposalNotification ? $this->proposalOwnership->resolve($context) : null;
        if ($isProposalNotification && ! $proposal) {
            return 0;
        }
        if ($proposal) {
            $context = [...$context, ...$this->proposalOwnership->context($proposal)];
        }

        $entry = str_starts_with($key, 'lab.') ? $this->ownership->resolve($context) : null;

        if (str_starts_with($key, 'lab.') && ! $entry) {
            return 0;
        }

        $hasLabContext = array_key_exists('lab_id', $context);
        $explicitLabId = filter_var($context['lab_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (($hasLabContext && $explicitLabId === false)
            || ($entry && $hasLabContext && $explicitLabId !== (int) $entry->lab_id)) {
            return 0;
        }

        $labId = $entry ? (int) $entry->lab_id : ($hasLabContext ? $explicitLabId : null);

        if ($entry) {
            $context = [...$context, ...$this->ownership->context($context, $entry)];
        }

        $payload = $this->render($key, $context);

        if (! $payload) {
            return 0;
        }

        $recipients = $notifiables instanceof Collection ? $notifiables : collect($notifiables);
        $recipients = $recipients->filter()->unique(fn ($notifiable) => get_class($notifiable).':'.$notifiable->getKey())->values();

        if ($recipients->isEmpty()) {
            return 0;
        }

        $sent = 0;

        foreach ($recipients as $recipient) {
            if ($proposal && ! $this->proposalOwnership->canReceive($recipient, $proposal)) {
                continue;
            }
            if ($entry && ! $this->ownership->canReceive($recipient, $entry)) {
                continue;
            }

            if (! $entry && $labId && $recipient instanceof User
                && ! $this->ownership->eligibleUsers($labId)->whereKey($recipient->getKey())->exists()) {
                continue;
            }

            $recipientPayload = $payload;

            if ($recipient instanceof User) {
                $recipientPayload['channels'] = $this->channelResolver->resolve(
                    $recipient,
                    $payload['category'],
                    $payload['priority'],
                    $payload['channels']
                );
            }

            if ($recipientPayload['channels'] === []) {
                continue;
            }

            Notification::send($recipient, new OperationalNotification($recipientPayload));
            $sent++;
        }

        return $sent;
    }

    /**
     * @param  array<string, scalar|null>  $context
     */
    private function interpolate(string $template, array $context): string
    {
        return Str::of($template)->replaceMatches('/{{\s*([a-zA-Z0-9_.-]+)\s*}}/', function (array $match) use ($context): string {
            $value = data_get($context, $match[1]);

            return is_scalar($value) ? (string) $value : '';
        })->squish()->toString();
    }
}
