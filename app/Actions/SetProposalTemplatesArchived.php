<?php

namespace App\Actions;

use App\Models\ISOActivityLog;
use App\Models\ProposalTemplate;
use App\Models\User;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SetProposalTemplatesArchived
{
    /** @param list<int> $recordIds */
    public function execute(int $userId, array $recordIds, bool $archived): int
    {
        Validator::make(['recordIds' => $recordIds], [
            'recordIds' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'recordIds.*' => ['required', 'integer', 'min:1', 'distinct'],
        ])->validate();

        return DB::transaction(function () use ($userId, $recordIds, $archived): int {
            $permission = $archived ? 'delete_proposal_templates' : 'restore_proposal_templates';
            $operator = $this->operator($userId, $permission);
            $records = VAPProposalTemplate::withTrashed()->whereKey($recordIds)->orderBy('id')->lockForUpdate()->get();
            abort_unless($records->count() === count($recordIds), 404);
            $activeIds = $records->reject(fn (VAPProposalTemplate $template): bool => $template->trashed())->modelKeys();
            if ($archived) {
                $this->assertUnused($activeIds);
            }

            $expected = [];
            foreach ($records as $record) {
                $expected[$record->id] = $record->getAttributes();
            }
            $history = $this->history($recordIds, $records->first()->getMorphClass());
            $audits = [];

            $changed = 0;
            foreach ($records as $record) {
                if ($record->trashed() === $archived) {
                    continue;
                }
                $startedAt = $record->freshTimestampString();
                abort_unless($archived ? $record->delete() : $record->restore(), 409, 'Não foi possível actualizar o arquivo do modelo.');
                $deletedAt = $archived ? ($record->getAttributes()['deleted_at'] ?? null) : null;
                $updatedAt = $record->getAttributes()['updated_at'] ?? null;
                abort_unless(! $archived || (is_string($deletedAt) && $deletedAt >= $startedAt && $deletedAt <= $record->freshTimestampString()),
                    409, 'A data de arquivo do modelo não corresponde à operação.');
                abort_unless(is_string($updatedAt) && $updatedAt >= $startedAt && $updatedAt <= $record->freshTimestampString(),
                    409, 'A data de actualização do modelo não corresponde à operação.');
                $expected[$record->id]['deleted_at'] = $deletedAt;
                $expected[$record->id]['updated_at'] = $updatedAt;
                $auditExpected = ['log_name' => config('activitylog.default_log_name'), 'event' => $archived ? 'archived' : 'restored',
                    'description' => $archived ? 'arquivou o modelo de proposta' : 'restaurou o modelo de proposta',
                    'subject_type' => $record->getMorphClass(), 'subject_id' => $record->id,
                    'causer_type' => $operator->getMorphClass(), 'causer_id' => $operator->id, 'properties' => []];
                $audit = activity()->causedBy($operator)->performedOn($record)
                    ->event($archived ? 'archived' : 'restored')->log($archived ? 'arquivou o modelo de proposta' : 'restaurou o modelo de proposta');
                abort_unless($audit?->exists, 409, 'Não foi possível registar o histórico do modelo.');
                $audits[$audit->id] = $auditExpected;
                $changed++;
            }
            if ($archived) {
                $this->assertUnused($activeIds);
            }
            $this->operator($userId, $permission);
            foreach ($records as $record) {
                $stored = VAPProposalTemplate::withTrashed()->find($record->id);
                abort_unless($stored && $stored->getAttributes() === $expected[$record->id],
                    409, 'O modelo guardado não corresponde ao arquivo solicitado.');
            }
            foreach ($audits as $id => $intended) {
                $stored = ISOActivityLog::withoutGlobalScopes()->find($id);
                abort_unless($stored, 409, 'O histórico do modelo não foi preservado.');
                $actual = $stored->only(array_keys($intended));
                $actual['subject_id'] = (int) $stored->subject_id;
                $actual['causer_id'] = (int) $stored->causer_id;
                $actual['properties'] = $stored->properties->all();
                abort_unless($actual === $intended, 409, 'O histórico guardado não corresponde à operação.');
            }
            $retained = $this->history($recordIds, $records->first()->getMorphClass());
            foreach (array_keys($audits) as $id) {
                unset($retained[$id]);
            }
            abort_unless($retained === $history, 409, 'O histórico anterior dos modelos não foi preservado.');

            return $changed;
        }, 3);
    }

    /** @param list<int> $templateIds */
    private function assertUnused(array $templateIds): void
    {
        if (VAPProposal::withoutGlobalScope('proposal_laboratory')->withTrashed()->whereIn('template_id', $templateIds)->exists()) {
            throw ValidationException::withMessages(['recordIds' => 'Não é possível arquivar um modelo usado por propostas.']);
        }
    }

    private function operator(int $userId, string $permission): User
    {
        $operator = User::query()->where('is_active', true)->whereNotNull('email_verified_at')->lockForUpdate()->find($userId);
        abort_unless($operator?->can($permission), 403);

        return $operator;
    }

    /** @param list<int> $recordIds
     * @return array<int,array<string,mixed>>
     */
    private function history(array $recordIds, string $subjectType): array
    {
        return ISOActivityLog::withoutGlobalScopes()->whereIn('subject_id', $recordIds)
            ->whereIn('subject_type', [$subjectType, ProposalTemplate::class, VAPProposalTemplate::class])
            ->orderBy('id')->lockForUpdate()->get()->mapWithKeys(fn (ISOActivityLog $audit): array => [$audit->id => $audit->getAttributes()])->all();
    }
}
