<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowTaskComment extends Model
{
    use HasUuids;

    public CONST MENU_NAME = 'workflow_task_comments';

    protected $table = 'workflow_task_comments';

    protected $fillable = [
        'task_id',
        'comment',
        'created_by',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(WorkflowTask::class, 'task_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}