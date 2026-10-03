<?php

namespace App\Actions;

use App\Models\User;
use App\Models\VAPSampleDiscard;
use App\Models\VAPSampleEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DiscardLaboratorySample
{
    /** @param array{sample_id: int|string, discard_method: string, qty: string, discarded_at?: ?string, lab_id?: int|string|null, department_id?: int|string|null} $data */
    public function execute(int $labId, User $operator, array $data): VAPSampleDiscard
    {
        return DB::transaction(function () use ($labId, $operator, $data): VAPSampleDiscard {
            $sample = VAPSampleEntry::query()->where('lab_id', $labId)->lockForUpdate()->findOrFail($data['sample_id']);

            if (! in_array($sample->status, ['COMPLETADO', 'CANCELADO'], true)) {
                throw ValidationException::withMessages(['sample_id' => 'Apenas podem ser descartadas amostras concluídas ou canceladas.']);
            }

            if ($sample->retention_status === 'discarded' || $sample->discards()->exists()) {
                throw ValidationException::withMessages(['sample_id' => 'O descarte desta amostra já foi registado.']);
            }

            $discard = VAPSampleDiscard::create([
                'sample_id' => $sample->id,
                'lab_id' => $sample->lab_id,
                'department_id' => $sample->department_id,
                'discard_method' => $data['discard_method'],
                'qty' => $data['qty'],
                'discarded_at' => $data['discarded_at'] ?? now(),
                'discarded_by_id' => $operator->id,
            ]);

            $sample->forceFill([
                'retention_status' => 'discarded',
                'discard_scheduled_at' => $discard->discarded_at->toDateString(),
            ])->save();

            return $discard;
        });
    }
}
