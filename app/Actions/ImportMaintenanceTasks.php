<?php

namespace App\Actions;

use App\Models\MaintenanceTaskImport;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Support\MaintenanceTaskCsv;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImportMaintenanceTasks
{
    public function __construct(
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly MaintenanceTaskCsv $csv,
        private readonly SaveMaintenanceTask $save,
    ) {}

    public function execute(int $labId, int $userId, string $path, string $requestKey): MaintenanceTaskImport
    {
        abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
        Validator::make(['request_key' => $requestKey], ['request_key' => ['required', 'uuid']])->validate();
        $fileHash = hash_file('sha256', $path);
        abort_unless(is_string($fileHash), 422, 'Não foi possível ler o ficheiro de importação.');

        return DB::transaction(function () use ($labId, $userId, $path, $requestKey, $fileHash): MaintenanceTaskImport {
            $this->access->operator($userId, $labId, 'add_maintenance_tasks');
            $existing = MaintenanceTaskImport::query()->lockForUpdate()->find($requestKey);
            if ($existing) {
                abort_unless($existing->lab_id === $labId && $existing->user_id === $userId, 404);
                if (! hash_equals($existing->file_hash, $fileHash)) {
                    throw ValidationException::withMessages(['file' => 'Esta operação já foi concluída com outro ficheiro. Seleccione novamente o CSV para iniciar uma nova importação.']);
                }

                return $existing;
            }
            $rows = $this->csv->rows($path, $labId);
            $taskIds = [];
            foreach ($rows as $index => $row) {
                try {
                    $taskIds[] = $this->save->execute($userId, $labId, $row)->id;
                } catch (ValidationException $exception) {
                    throw ValidationException::withMessages(['file' => 'Registo '.($index + 1).': '.$exception->validator->errors()->first()]);
                }
            }
            $receipt = new MaintenanceTaskImport([
                'id' => $requestKey, 'lab_id' => $labId, 'user_id' => $userId,
                'file_hash' => $fileHash, 'row_count' => count($taskIds), 'task_ids' => $taskIds,
            ]);
            abort_unless($receipt->save(), 409, 'Não foi possível confirmar a importação. Nenhuma tarefa foi guardada.');
            $this->access->operator($userId, $labId, 'add_maintenance_tasks');

            return $receipt;
        }, 3);
    }
}
