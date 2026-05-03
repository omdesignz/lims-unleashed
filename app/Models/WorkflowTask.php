<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowTask extends Model
{
    use HasUuids;

    public CONST MENU_NAME = 'workflow_tasks';

    protected $table = 'workflow_tasks';

    protected $fillable = [
        'file_id',
        'type',
        'assigned_to',
        'status',
        'due_date',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(VAPFile::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(WorkflowTaskComment::class, 'task_id');
    }
}