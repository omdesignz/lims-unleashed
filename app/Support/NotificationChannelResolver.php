<?php

namespace App\Support;

use App\Models\NotificationPreference;
use App\Models\User;

class NotificationChannelResolver
{
    /**
     * @param  array<int, string>  $templateChannels
     * @return array<int, string>
     */
    public function resolve(User $user, string $category, string $priority, array $templateChannels): array
    {
        if ($priority === 'urgent') {
            return $templateChannels;
        }

        /** @var NotificationPreference|null $preference */
        $preference = $user->relationLoaded('notificationPreferences')
            ? $user->notificationPreferences->firstWhere('category', $category)
            : $user->notificationPreferences()->where('category', $category)->first();

        if (! $preference) {
            return $templateChannels;
        }

        $channels = collect($templateChannels)->filter(fn (string $channel): bool => match ($channel) {
            'database' => $preference->database_enabled,
            'broadcast' => $preference->broadcast_enabled,
            'mail' => $preference->mail_enabled,
            default => false,
        });

        if ($this->isQuietHours($preference)) {
            $channels = $channels->reject(fn (string $channel): bool => in_array($channel, ['broadcast', 'mail'], true));
        }

        return $channels->values()->all();
    }

    private function isQuietHours(NotificationPreference $preference): bool
    {
        if (! $preference->quiet_hours_start || ! $preference->quiet_hours_end) {
            return false;
        }

        $now = now($preference->timezone ?: config('app.timezone'))->format('H:i:s');
        $start = $preference->quiet_hours_start;
        $end = $preference->quiet_hours_end;

        if ($start === $end) {
            return false;
        }

        return $start < $end
            ? $now >= $start && $now < $end
            : $now >= $start || $now < $end;
    }
}
