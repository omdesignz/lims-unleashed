<?php

namespace App\Actions;

use App\Http\Requests\SaveNonConformityRequest;
use App\Models\VAPNonConformity;
use App\Models\VAPNonConformityAction;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\NonConformityEvidence;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SaveNonConformity
{
    public function __construct(
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly NonConformityEvidence $evidence,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(int $userId, int $labId, array $data, ?int $recordId = null): VAPNonConformity
    {
        return DB::transaction(function () use ($userId, $labId, $data, $recordId): VAPNonConformity {
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            $permission = ($recordId ? 'edit' : 'add').'_occurrences';
            $operator = $this->access->operator($userId, $labId, $permission);
            $record = $recordId
                ? VAPNonConformity::where('lab_id', $labId)->lockForUpdate()->findOrFail($recordId)
                : new VAPNonConformity;
            $validated = Validator::make($data, SaveNonConformityRequest::authoringRules($labId, $recordId, $record->status))->validate();
            $before = $record->only($record->getFillable());
            $wasResolved = $record->status === 'resolved';
            $record->fill(Arr::except($validated, ['actions', 'attachment_files']));
            $coreChanged = count(Arr::except($record->getDirty(), ['comments'])) > 0;
            $changed = $record->isDirty();
            if (! $record->exists) {
                $record->lab_id = $labId;
                $record->reported_by_id = $userId;
            }
            if (! $record->exists || $record->isDirty()) {
                abort_unless($record->save(), 409, 'Não foi possível guardar a não conformidade.');
            }

            if (array_key_exists('actions', $validated)) {
                $existing = $record->actions()->where('lab_id', $labId)->lockForUpdate()->get()->keyBy('id');
                $retainedIds = [];
                foreach ($validated['actions'] ?? [] as $actionData) {
                    $action = isset($actionData['id']) ? $existing->get($actionData['id']) : new VAPNonConformityAction;
                    abort_unless($action, 404);
                    $action->fill(Arr::only($actionData, ['correction', 'corrective_action', 'due_at']));
                    if (! $action->exists) {
                        $action->lab_id = $labId;
                        $action->nc_id = $record->id;
                    }
                    if (! $action->exists || $action->isDirty()) {
                        $coreChanged = true;
                        abort_unless($action->save(), 409, 'Não foi possível guardar a acção correctiva.');
                    }
                    $retainedIds[] = $action->id;
                }
                foreach ($existing->except($retainedIds) as $removed) {
                    $coreChanged = true;
                    abort_unless($removed->delete(), 409, 'Não foi possível arquivar a acção correctiva.');
                }
            }
            $this->evidence->add($record, $validated['attachment_files'] ?? []);
            $coreChanged = $coreChanged || filled($validated['attachment_files'] ?? []);
            if ($recordId && ($changed || $coreChanged)) {
                $record->workflow_revision++;
                $history = $record->workflow_history ?? [];
                $history[] = ['action' => $wasResolved && $coreChanged ? 'verification_invalidated' : 'edited',
                    'revision' => $record->workflow_revision, 'actor_id' => $userId, 'actor_name' => $operator->name,
                    'at' => now()->toIso8601String(), 'from' => $before['status'], 'to' => $record->status,
                    'evidence' => $wasResolved && $coreChanged ? 'Dossier alterado; exige nova verificação.' : 'Observações ou dados actualizados.',
                    'before' => $before, 'after' => $record->only($record->getFillable())];
                $record->workflow_history = $history;
                if ($wasResolved && $coreChanged) {
                    $record->verified_at = null;
                    $record->verification_evidence = null;
                }
                abort_unless($record->save(), 409, 'Não foi possível guardar o histórico.');
            }
            $this->access->operator($userId, $labId, $permission);

            return $record;
        }, 3);
    }
}
