<?php

namespace App\Actions;

use App\Models\VAPNonConformity;
use App\Services\LaboratoryWorkflowMutationAccess;
use Illuminate\Support\Facades\DB;

class ArchiveNonConformity
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access) {}

    public function execute(int $userId, int $labId, int $recordId, bool $restore = false): void
    {
        DB::transaction(function () use ($userId, $labId, $recordId, $restore): void {
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            $permission = ($restore ? 'restore' : 'delete').'_occurrences';
            $this->access->operator($userId, $labId, $permission);
            $record = VAPNonConformity::withTrashed()->where('lab_id', $labId)->lockForUpdate()->findOrFail($recordId);
            if ($restore && $record->trashed()) {
                abort_unless($record->restore(), 409, 'Não foi possível restaurar a não conformidade.');
            } elseif (! $restore && ! $record->trashed()) {
                abort_unless($record->delete(), 409, 'Não foi possível arquivar a não conformidade.');
            }
            $this->access->operator($userId, $labId, $permission);
        }, 3);
    }
}
