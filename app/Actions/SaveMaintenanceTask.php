<?php

namespace App\Actions;

use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceTask;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Support\MaintenanceTaskValidation;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SaveMaintenanceTask
{
    public function __construct(
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly CompleteMaintenanceTasks $complete,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(int $userId, int $labId, array $data, ?int $taskId = null): MaintenanceTask
    {
        return DB::transaction(function () use ($userId, $labId, $data, $taskId): MaintenanceTask {
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            $permission = $taskId ? 'edit_maintenance_tasks' : 'add_maintenance_tasks';
            $this->access->operator($userId, $labId, $permission);
            $task = $taskId
                ? MaintenanceTask::forLaboratory($labId)->lockForUpdate()->findOrFail($taskId)
                : new MaintenanceTask;
            $validated = Validator::make(
                MaintenanceTaskValidation::mergeCurrentState($data, $taskId ? $task : null),
                MaintenanceTaskValidation::rules($labId, $taskId ? $task : null),
                MaintenanceTaskValidation::messages(),
            )->validate();

            InventoryItem::forLaboratory($labId)->equipment()->lockForUpdate()->findOrFail($validated['equipment_id']);
            MaintenanceCategory::availableToLaboratory($labId)->when($taskId, fn ($query) => $query->withTrashed())
                ->lockForUpdate()->findOrFail($validated['category_id']);
            if (! empty($validated['supplier_id'])) {
                InventoryItemSupplier::query()->lockForUpdate()->findOrFail($validated['supplier_id']);
            }

            $completing = (bool) $validated['is_executed'] && ! $task->is_executed;
            $task->fill(Arr::only($validated, MaintenanceTaskValidation::EDITABLE_FIELDS));
            if (! $task->exists) {
                $task->maintenance_task_year = now()->year;
            }
            if (! $task->exists || $task->isDirty(['due_date', 'periodicity', 'periodicity_unit'])) {
                $task->next_date = $task->periodicity && $task->periodicity_unit
                    ? Carbon::parse($task->due_date)->add($task->periodicity_unit, (int) $task->periodicity)
                    : null;
            }
            if ($completing) {
                $task->is_executed = false;
            }
            if (! $task->exists || $task->isDirty()) {
                abort_unless($task->save(), 409, 'Não foi possível guardar a tarefa. Nenhuma alteração foi guardada.');
            }
            if ($completing) {
                $this->complete->execute($userId, $labId, [$task->id]);
                $task->refresh();
            }
            $this->access->operator($userId, $labId, $permission);

            return $task;
        }, 3);
    }
}
