<?php

namespace App\Actions;

use App\Models\ISOActivityLog;
use App\Models\ProposalTemplate;
use App\Models\User;
use App\Models\VAPProposalTemplate;
use Illuminate\Support\Facades\DB;

class SetProposalTemplateActiveStatus
{
    public function execute(int $userId, int $templateId, bool $active): VAPProposalTemplate
    {
        return DB::transaction(function () use ($userId, $templateId, $active): VAPProposalTemplate {
            $operator = $this->operator($userId);
            $template = VAPProposalTemplate::query()->lockForUpdate()->findOrFail($templateId);
            if ($template->is_active === $active) {
                $this->operator($userId);

                return $template;
            }

            $history = $this->history($template);
            $previousStatus = $template->is_active;
            $template->is_active = $active;
            $template->setUpdatedAt($template->freshTimestamp());
            $expected = $template->getAttributes();
            abort_unless(VAPProposalTemplate::withoutTimestamps(fn (): bool => $template->save()),
                409, 'Não foi possível actualizar o estado do modelo.');

            $description = $active ? 'proposal_template_activado' : 'proposal_template_desactivado';
            $properties = ['previous_is_active' => $previousStatus, 'is_active' => $active];
            $auditExpected = ['log_name' => config('activitylog.default_log_name'), 'event' => 'activation_changed',
                'description' => $description, 'subject_type' => $template->getMorphClass(), 'subject_id' => $templateId,
                'causer_type' => $operator->getMorphClass(), 'causer_id' => $userId, 'properties' => $properties];
            $audit = activity()->performedOn($template)->causedBy($operator)->event('activation_changed')
                ->withProperties($properties)->log($description);
            abort_unless($audit?->exists, 409, 'Não foi possível registar o histórico do modelo.');

            $this->operator($userId);
            $stored = VAPProposalTemplate::withTrashed()->find($templateId);
            abort_unless($stored && $stored->getAttributes() === $expected,
                409, 'O modelo guardado não corresponde ao estado solicitado.');
            $storedAudit = ISOActivityLog::withoutGlobalScopes()->find($audit->id);
            abort_unless($storedAudit, 409, 'O histórico do modelo não foi preservado.');
            $actual = $storedAudit->only(array_keys($auditExpected));
            $actual['subject_id'] = (int) $storedAudit->subject_id;
            $actual['causer_id'] = (int) $storedAudit->causer_id;
            $actual['properties'] = $storedAudit->properties->all();
            abort_unless($actual === $auditExpected, 409, 'O histórico guardado não corresponde à operação.');
            $retained = $this->history($stored);
            unset($retained[$audit->id]);
            abort_unless($retained === $history, 409, 'O histórico anterior do modelo não foi preservado.');

            return $stored;
        }, 3);
    }

    private function operator(int $userId): User
    {
        $operator = User::query()->where('is_active', true)->whereNotNull('email_verified_at')->lockForUpdate()->find($userId);
        abort_unless($operator?->can('edit_proposal_templates'), 403);

        return $operator;
    }

    /** @return array<int, array<string, mixed>> */
    private function history(VAPProposalTemplate $template): array
    {
        return ISOActivityLog::withoutGlobalScopes()->where('subject_id', $template->id)
            ->whereIn('subject_type', [$template->getMorphClass(), ProposalTemplate::class, VAPProposalTemplate::class])
            ->orderBy('id')->lockForUpdate()->get()->mapWithKeys(fn (ISOActivityLog $audit): array => [$audit->id => $audit->getAttributes()])->all();
    }
}
