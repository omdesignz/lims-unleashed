<?php

namespace App\Actions;

use App\Models\MaintenanceTask;
use App\Services\LaboratoryWorkflowMutationAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateMaintenanceTaskLifecycle
{
    public function __construct(
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly SaveMaintenanceTask $save,
    ) {}

    /** @param list<int> $taskIds */
    public function execute(int $userId, int $labId, array $taskIds, string $action, ?string $newDate = null): void
    {
        Validator::make(['task_ids' => $taskIds, 'action' => $action, 'new_date' => $newDate], [
            'task_ids' => ['required', 'array', 'min:1', 'max:100'],
            'task_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'action' => ['required', Rule::in(['reschedule', 'delete', 'restore'])],
            'new_date' => ['nullable', 'required_if:action,reschedule', 'date_format:Y-m-d'],
        ])->validate();

        DB::transaction(function () use ($userId, $labId, $taskIds, $action, $newDate): void {
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            $permission = match ($action) {
                'delete' => 'delete_maintenance_tasks',
                'restore' => 'restore_maintenance_tasks',
                'reschedule' => 'edit_maintenance_tasks',
            };
            $this->access->operator($userId, $labId, $permission);
            $tasks = MaintenanceTask::forLaboratory($labId)
                ->when($action !== 'reschedule', fn ($query) => $query->withTrashed())
                ->whereKey($taskIds)->orderBy('id')->lockForUpdate()->get();
            abort_unless($tasks->count() === count($taskIds), 404);

            foreach ($tasks as $task) {
                if ($action === 'reschedule') {
                    $this->save->execute($userId, $labId, ['due_date' => $newDate], $task->id);
                } elseif ($action === 'delete' && ! $task->trashed()) {
                    abort_unless($task->delete(), 409, 'Não foi possível arquivar todas as tarefas. Nenhuma alteração foi guardada.');
                } elseif ($action === 'restore' && $task->trashed()) {
                    abort_unless($task->restore(), 409, 'Não foi possível restaurar todas as tarefas. Nenhuma alteração foi guardada.');
                }
            }
            $this->access->operator($userId, $labId, $permission);
        }, 3);
    }
}
