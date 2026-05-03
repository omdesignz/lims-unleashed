<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkflowTaskRequest;
use App\Http\Resources\WorkflowTaskResource;
use App\Models\WorkflowTask;
use App\Models\WorkflowTaskComment;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function index(Request $request)
    {
        $tasks = WorkflowTask::query()
            ->with(['comments.creator', 'assignee', 'file'])
            ->when($request->has('file_id'), function ($query) use ($request) {
                $query->where('file_id', $request->file_id);
            })
            ->when($request->has('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->whereHas('file', function ($query) use ($request) {
                $user = $request->user();

                if (! $user) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
                    return;
                }

                $query->where('created_by', $user->id)
                    ->orWhereHas('permissions', function ($permissionQuery) use ($user) {
                        $permissionQuery->where('user_id', $user->id);
                    });
            })
            ->get();

        return WorkflowTaskResource::collection($tasks);
    }

    public function store(WorkflowTaskRequest $request)
    {
        $file = \App\Models\VAPFile::query()->findOrFail($request->file_id);
        abort_unless($file->canBeWrittenBy($request->user()), 403);

        $task = WorkflowTask::create($request->all());

        return new WorkflowTaskResource($task->load(['comments', 'assignee', 'file']));
    }

    public function updateStatus(Request $request, WorkflowTask $task)
    {
        abort_unless($task->file && $task->file->canBeWrittenBy($request->user()), 403);

        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,rejected',
        ]);

        $task->update([
            'status' => $request->status,
            'completed_at' => in_array($request->status, ['completed', 'rejected']) ? now() : null,
        ]);

        return new WorkflowTaskResource($task->load(['comments', 'assignee', 'file']));
    }

    public function addComment(Request $request, WorkflowTask $task)
    {
        abort_unless($task->file && $task->file->canBeWrittenBy($request->user()), 403);

        $request->validate([
            'comment' => 'required|string',
        ]);

        $comment = $task->comments()->create([
            'comment' => $request->comment,
            'created_by' => auth()->id(),
        ]);

        return new WorkflowTaskResource($task->load(['comments.creator', 'assignee']));
    }
}
