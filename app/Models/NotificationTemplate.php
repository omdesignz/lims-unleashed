<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationTemplate extends Model
{
    use HasFactory;

    public const EDITABLE_FIELDS = [
        'title_template', 'in_app_template', 'email_subject_template', 'email_template',
        'action_label_template', 'action_url_template', 'channels', 'priority', 'enabled',
    ];

    protected $fillable = [
        'key',
        'lab_id',
        'updated_by_id',
        'title_template',
        'in_app_template',
        'email_subject_template',
        'email_template',
        'action_label_template',
        'action_url_template',
        'channels',
        'priority',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'enabled' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $template): void {
            if ($template->isDirty(['lab_id', 'key'])) {
                throw new \LogicException('Notification override identity is immutable.');
            }
        });
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class, 'lab_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
