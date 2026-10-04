<?php

namespace App\Actions;

use App\Http\Requests\TransitionNonConformityRequest;
use App\Models\VAPNonConformity;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Support\QualityModuleNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TransitionNonConformity
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access, private readonly QualityModuleNotifier $notifier) {}

    /** @param array<string, mixed> $data */
    public function execute(int $userId, int $labId, int $recordId, string $transition, array $data): VAPNonConformity
    {
        abort_unless(in_array($transition, ['resolve', 'verify', 'close', 'reopen'], true), 404);

        return DB::transaction(function () use ($userId, $labId, $recordId, $transition, $data): VAPNonConformity {
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            $permission = $transition.'_non_conformities';
            $operator = $this->access->operator($userId, $labId, $permission);
            $record = VAPNonConformity::where('lab_id', $labId)->lockForUpdate()->findOrFail($recordId);
            $validated = Validator::make($data, TransitionNonConformityRequest::transitionRules($transition))->validate();
            $history = $record->workflow_history ?? [];
            $previous = collect($history)->firstWhere('request_id', $validated['request_id']);
            $evidence = trim($validated['evidence'] ?? '');
            if ($previous) {
                abort_unless($previous['action'] === $transition && $previous['actor_id'] === $userId && $previous['evidence'] === $evidence, 409);
                $this->notifyAfterCommit($record, $validated['request_id']);

                return $record;
            }
            abort_unless($record->workflow_revision === (int) $validated['workflow_revision'], 409, 'O dossier foi alterado. Actualize a página antes de continuar.');
            $allowed = match ($transition) {
                'resolve' => in_array($record->status, ['opened', 'in_progress'], true),
                'verify' => $record->status === 'resolved' && filled($record->resolution_evidence) && $record->resolved_at && ! $record->verified_at,
                'close' => $record->status === 'resolved' && filled($record->resolution_evidence) && filled($record->verification_evidence) && $record->verified_at,
                'reopen' => in_array($record->status, ['resolved', 'closed'], true),
            };
            if (! $allowed) {
                throw ValidationException::withMessages(['transition' => 'Esta etapa exige a conclusão da etapa anterior. Reabra o dossier para iniciar um novo ciclo.']);
            }
            $from = $record->status;
            if ($transition === 'resolve') {
                $record->status = 'resolved';
                $record->resolved_at = now();
                $record->resolution_evidence = $evidence;
                $record->verified_at = null;
                $record->verification_evidence = null;
            } elseif ($transition === 'verify') {
                $record->verified_at = now();
                $record->verification_evidence = $evidence;
            } elseif ($transition === 'close') {
                $record->status = 'closed';
                $record->closed_at = now();
            } else {
                $record->status = 'in_progress';
                $record->resolved_at = null;
                $record->resolution_evidence = null;
                $record->verified_at = null;
                $record->verification_evidence = null;
                $record->closed_at = null;
            }
            $record->workflow_revision++;
            $history[] = [
                'request_id' => $validated['request_id'], 'revision' => $record->workflow_revision,
                'action' => $transition, 'from' => $from, 'to' => $record->status,
                'actor_id' => $userId, 'actor_name' => $operator->name, 'at' => now()->toIso8601String(), 'evidence' => $evidence,
                'snapshot' => [
                    'dossier' => $record->only($record->getFillable()),
                    'actions' => $record->actions()->withTrashed()->where('lab_id', $labId)->orderBy('id')->get()->toArray(),
                    'attachment_ids' => $record->media()->where('collection_name', 'attachments')->orderBy('id')->pluck('id')->all(),
                ],
            ];
            $record->workflow_history = $history;
            abort_unless($record->save(), 409, 'Não foi possível guardar a transição.');
            $this->access->operator($userId, $labId, $permission);
            $this->notifyAfterCommit($record, $validated['request_id']);

            return $record;
        }, 3);
    }

    private function notifyAfterCommit(VAPNonConformity $record, string $requestId): void
    {
        DB::afterCommit(function () use ($record, $requestId): void {
            try {
                $this->notifier->notifyNonConformityTransition($record, $requestId);
            } catch (\Throwable $exception) {
                report($exception);
            }
        });
    }
}
