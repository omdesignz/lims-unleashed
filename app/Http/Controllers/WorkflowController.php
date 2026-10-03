<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkflowTaskRequest;
use App\Http\Resources\WorkflowTaskResource;
use App\Models\VAPFile;
use App\Models\WorkflowTask;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkflowController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $laboratory) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $labId = $this->laboratory->activeLabId();
        $tasks = WorkflowTask::query()
            ->with(['comments.creator', 'assignee', 'file'])
            ->when($request->has('file_id'), function ($query) use ($request) {
                $query->where('file_id', $request->file_id);
            })
            ->when($request->has('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->whereHas('file', function ($query) use ($request, $labId) {
                $query->where('lab_id', $labId);
                $user = $request->user();

                if (! $user) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
                    return;
                }

                $query->where(function ($accessible) use ($user) {
                    $accessible->where('created_by', $user->id)
                        ->orWhereHas('permissions', function ($permissionQuery) use ($user) {
                            $permissionQuery->where('user_id', $user->id);
                        });
                });
            })
            ->get();

        return WorkflowTaskResource::collection($tasks);
    }

    public function store(WorkflowTaskRequest $request): WorkflowTaskResource
    {
        $validated = $request->validated();
        $file = VAPFile::query()->where('lab_id', $this->laboratory->activeLabId())
            ->findOrFail($validated['file_id']);
        abort_unless($file->canBeWrittenBy($request->user()), 403);

        $task = WorkflowTask::query()->create($validated);

        return new WorkflowTaskResource($task->load(['comments', 'assignee', 'file']));
    }

    public function updateStatus(Request $request, WorkflowTask $task): WorkflowTaskResource
    {
        $file = $task->file;
        abort_unless($file && (int) $file->lab_id === $this->laboratory->activeLabId(), 404);
        abort_unless($file->canBeWrittenBy($request->user()), 403);

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,rejected',
        ]);

        $task->update([
            'status' => $validated['status'],
            'completed_at' => in_array($validated['status'], ['completed', 'rejected'], true) ? now() : null,
        ]);

        return new WorkflowTaskResource($task->load(['comments', 'assignee', 'file']));
    }

    public function addComment(Request $request, WorkflowTask $task): WorkflowTaskResource
    {
        $file = $task->file;
        abort_unless($file && (int) $file->lab_id === $this->laboratory->activeLabId(), 404);
        abort_unless($file->canBeWrittenBy($request->user()), 403);

        $validated = $request->validate([
            'comment' => 'required|string',
        ]);

        $task->comments()->create([
            'comment' => $validated['comment'],
            'created_by' => $request->user()->id,
        ]);

        return new WorkflowTaskResource($task->load(['comments.creator', 'assignee']));
    }
}
