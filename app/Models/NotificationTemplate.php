<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'key',
        'name',
        'category',
        'description',
        'audience_permission',
        'title_template',
        'in_app_template',
        'email_subject_template',
        'email_template',
        'action_label_template',
        'action_url_template',
        'channels',
        'variables',
        'priority',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'variables' => 'array',
            'enabled' => 'boolean',
        ];
    }
}
