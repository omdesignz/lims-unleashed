<?php

namespace App\Actions;

use App\Models\InventoryItem;
use App\Models\MaintenanceTask;
use App\Services\LaboratoryWorkflowMutationAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteMaintenanceTasks
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access) {}

    /** @param list<int> $taskIds */
    public function execute(int $userId, int $labId, array $taskIds): void
    {
        DB::transaction(function () use ($userId, $labId, $taskIds): void {
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            $this->access->operator($userId, $labId, 'edit_maintenance_tasks');
            $tasks = MaintenanceTask::forLaboratory($labId)->whereKey($taskIds)->orderBy('id')
                ->lockForUpdate()->with('category')->get();
            abort_unless($tasks->isNotEmpty() && $tasks->count() === count(array_unique($taskIds)), 404);
            $equipment = InventoryItem::forLaboratory($labId)->equipment()->whereKey($tasks->pluck('equipment_id')->unique())
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($tasks as $task) {
                abort_unless($equipment->has($task->equipment_id), 404);
                if (blank($task->result)) {
                    throw ValidationException::withMessages([
                        'task_ids' => 'Registe o resultado de cada tarefa seleccionada antes de concluir. Tarefa sem resultado: '.$task->maintenance_task_no,
                    ]);
                }
            }
            foreach ($tasks as $task) {
                if ($task->is_executed) {
                    continue;
                }
                $payload = ['is_executed' => true, 'previous_date' => $task->due_date];
                if ($task->periodicity && $task->periodicity_unit && $task->due_date) {
                    $payload['due_date'] = $task->due_date->copy()->add($task->periodicity_unit, (int) $task->periodicity);
                    $payload['next_date'] = $payload['due_date']->copy()->add($task->periodicity_unit, (int) $task->periodicity);
                }
                abort_unless($task->update($payload), 409, 'Não foi possível concluir todas as tarefas. Nenhuma alteração foi guardada.');
                if (in_array($task->category?->code, ['CAL_INT', 'CAL_EXT'], true)) {
                    abort_unless($equipment[$task->equipment_id]->update([
                        'last_calibration_date' => $task->previous_date,
                        'next_calibration_date' => $task->due_date,
                    ]), 409, 'Não foi possível actualizar a calibração do equipamento.');
                }
            }
            $this->access->operator($userId, $labId, 'edit_maintenance_tasks');
        });
    }
}
