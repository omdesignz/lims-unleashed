<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait HasDocumentRevisions
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        $loggable = collect($this->fillable ?? [])
            ->reject(fn ($field) => in_array($field, ['unique_hash', 'file_path'], true))
            ->values()
            ->all();

        return LogOptions::defaults()
            ->logOnly($loggable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(
                fn (string $eventName) => sprintf('%s %s', class_basename($this), $eventName)
            );
    }

    public function getRevisionCountAttribute(): int
    {
        if (array_key_exists('revision_count', $this->attributes)) {
            return (int) $this->attributes['revision_count'];
        }

        return $this->activities()->count();
    }

    public function getLastRevisionAtAttribute()
    {
        if (array_key_exists('last_revision_at', $this->attributes)) {
            return $this->attributes['last_revision_at'];
        }

        return optional($this->activities()->latest('created_at')->first())->created_at;
    }
}
