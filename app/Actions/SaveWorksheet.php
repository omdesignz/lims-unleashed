<?php

namespace App\Actions;

use App\Models\Worksheet;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\LaboratoryWorksheetAccess;
use App\Support\WorksheetValidation;
use Illuminate\Support\Facades\DB;

class SaveWorksheet
{
    public function __construct(
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly LaboratoryWorksheetAccess $worksheets,
        private readonly WorksheetValidation $validation,
    ) {}

    /** @param array{name: string, worksheets: array{sheets: array}} $data */
    public function execute(int $labId, int $userId, array $data, ?int $worksheetId = null): Worksheet
    {
        return DB::transaction(function () use ($labId, $userId, $data, $worksheetId): Worksheet {
            $operator = $this->access->operator($userId, $labId, $worksheetId === null ? 'add_worksheets' : 'edit_worksheets');
            $data = $this->validation->validate($data);

            if ($worksheetId === null) {
                $worksheet = Worksheet::query()->create([
                    'lab_id' => $labId, 'user_id' => $operator->id,
                    'name' => $data['name'], 'worksheets' => ['sheets' => $data['worksheets']['sheets']],
                ]);
                abort_unless($worksheet->exists, 409, 'A criação de folha de trabalho foi cancelada.');
                $description = 'criou a folha de trabalho';
            } else {
                $worksheet = $this->worksheets->lockWorksheet($labId, $worksheetId);
                $payload = $worksheet->worksheets;
                $payload['sheets'] = $data['worksheets']['sheets'];
                $worksheet->fill(['name' => $data['name'], 'worksheets' => $payload]);
                if (! $worksheet->isDirty()) {
                    return $worksheet;
                }
                abort_unless($worksheet->save(), 409, 'A alteração da folha de trabalho foi cancelada.');
                $description = 'actualizou a folha de trabalho';
            }

            activity()->causedBy($operator)->performedOn($worksheet)->withProperties(['lab_id' => $labId])->log($description);

            return $worksheet;
        }, 3);
    }
}
