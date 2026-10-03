<?php

namespace App\Actions;

use App\Models\VAPProposal;
use App\Services\ProposalNotificationOwnership;
use App\Support\ProposalWorkflowNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class RecordPublicProposalDecision
{
    public function __construct(
        private readonly ProposalNotificationOwnership $ownership,
        private readonly ProposalWorkflowNotifier $notifier
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(VAPProposal $boundProposal, bool $accepted, array $data, ?string $clientIp): VAPProposal
    {
        $validated = Validator::make($data, self::rules($accepted))->validate();
        $context = $this->ownership->context($boundProposal);

        return DB::transaction(function () use ($boundProposal, $accepted, $validated, $clientIp, $context): VAPProposal {
            $proposal = VAPProposal::withoutGlobalScope('proposal_laboratory')
                ->whereKey($boundProposal->getKey())->where('unique_hash', $context['proposal_hash'])
                ->lockForUpdate()->firstOrFail();
            abort_unless(filled($context['proposal_hash']) && $this->ownership->resolve($context), 404);
            abort_unless(in_array($proposal->status, ['SENT', 'VIEWED', 'REVISED'], true), 400,
                $accepted ? 'A proposta não pode ser aceite no estado actual.' : 'A proposta não pode ser rejeitada no estado actual.');

            $agreements = $proposal->complianceAgreement()->withoutGlobalScope('proposal_laboratory')->lockForUpdate()->get();
            abort_unless($agreements->count() <= 1, 409, 'Existe mais de um acordo activo para esta proposta.');
            $agreement = $agreements->first() ?? $proposal->complianceAgreement()->make();
            $agreement->fill([
                'confidentiality' => $accepted ? $validated['confidentiality'] : false,
                'impartiality' => $accepted ? $validated['impartiality'] : false,
                'nondisclosure' => $accepted ? $validated['nondisclosure'] : false,
                'acknowledged_at' => $accepted ? now() : null,
                'rejected_at' => $accepted ? null : now(),
                'rejection_reason' => $accepted ? null : $validated['reason'],
                'client_ip' => $clientIp,
            ]);
            abort_unless($agreement->save(), 409, 'Não foi possível registar o acordo.');

            if ($accepted) {
                $log = $proposal->complianceAgreementLogs()->make([
                    'confidentiality' => $validated['confidentiality'],
                    'impartiality' => $validated['impartiality'],
                    'nondisclosure' => $validated['nondisclosure'],
                    'acknowledged_at' => $agreement->acknowledged_at,
                    'client_ip' => $clientIp,
                ]);
                abort_unless($log->save(), 409, 'Não foi possível registar a evidência de aceitação.');
            }

            $proposal->status = $accepted ? 'ACCEPTED' : 'REJECTED';
            if (! $accepted) {
                $proposal->obs = ($proposal->obs ? $proposal->obs."\n\n" : '').'Motivo da rejeição: '.$validated['reason'];
            }
            abort_unless($proposal->save(), 409, 'Não foi possível registar a decisão.');
            $audit = activity()->performedOn($proposal)
                ->withProperties($accepted ? ['client_ip' => $clientIp] : ['reason' => $validated['reason']])
                ->log($accepted ? 'accepted' : 'rejected');
            abort_unless($audit?->exists, 409, 'Não foi possível registar o histórico da decisão.');

            DB::afterCommit(function () use ($context, $accepted): void {
                try {
                    $current = $this->ownership->resolve($context);
                    if ($current && $current->status === ($accepted ? 'ACCEPTED' : 'REJECTED')) {
                        $accepted ? $this->notifier->notifyAccepted($current) : $this->notifier->notifyRejected($current);
                    }
                } catch (Throwable $exception) {
                    report($exception);
                }
            });

            return $proposal;
        }, 3);
    }

    /** @return array<string, list<string>> */
    public static function rules(bool $accepted): array
    {
        return $accepted ? [
            'confidentiality' => ['required', 'boolean'],
            'impartiality' => ['required', 'boolean'],
            'nondisclosure' => ['required', 'boolean'],
        ] : ['reason' => ['required', 'string', 'min:10']];
    }
}
