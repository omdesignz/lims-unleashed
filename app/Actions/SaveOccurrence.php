<?php

namespace App\Actions;

use App\Models\Occurrence;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Support\OccurrenceValidation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SaveOccurrence
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access) {}

    /** @param array<string, mixed> $data */
    public function execute(int $labId, int $userId, array $data, ?int $occurrenceId = null): Occurrence
    {
        return DB::transaction(function () use ($labId, $userId, $data, $occurrenceId): Occurrence {
            $operator = $this->access->operator($userId, $labId, $occurrenceId ? 'edit_occurrences' : 'add_occurrences');
            $occurrence = $occurrenceId
                ? Occurrence::query()->where('lab_id', $labId)->lockForUpdate()->findOrFail($occurrenceId)
                : new Occurrence;

            $validated = Validator::make(OccurrenceValidation::normalize($data), OccurrenceValidation::rules($labId))->validate();
            $occurrence->fill($validated);

            if (! $occurrence->exists) {
                $occurrence->lab_id = $labId;
                $occurrence->occurrence_year = (string) now()->year;
            }

            abort_unless($occurrence->save(), 409);
            activity()->causedBy($operator)->performedOn($occurrence)->withProperties(['lab_id' => $labId])
                ->log($occurrenceId ? 'actualizou a ocorrência' : 'registou a ocorrência');

            return $occurrence;
        }, 3);
    }
}
