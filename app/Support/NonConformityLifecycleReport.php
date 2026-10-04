<?php

namespace App\Support;

use App\Models\VAPNonConformity;
use Carbon\CarbonImmutable;

class NonConformityLifecycleReport
{
    /** @return array<int, array{0: string, 1: string}> */
    public static function summary(VAPNonConformity $record): array
    {
        return array_map(fn (array $row): array => [$row[0], $row[1] ?? 'Não registada'], [
            ['Revisão do fluxo', (string) $record->workflow_revision],
            ['Resolvida em', $record->resolved_at?->format('d/m/Y H:i:s')],
            ['Evidência de resolução', $record->resolution_evidence],
            ['Verificada em', $record->verified_at?->format('d/m/Y H:i:s')],
            ['Evidência de verificação', $record->verification_evidence],
            ['Encerrada em', $record->closed_at?->format('d/m/Y H:i:s')],
        ]);
    }

    /** @return array<int, array{revision: string, action: string, from: string, to: string, actor: string, at: string, evidence: string}> */
    public static function history(VAPNonConformity $record): array
    {
        $actions = ['resolve' => 'Resolução', 'verify' => 'Verificação', 'close' => 'Encerramento', 'reopen' => 'Reabertura',
            'edited' => 'Edição', 'verification_invalidated' => 'Verificação invalidada'];
        $states = ['opened' => 'Aberta', 'in_progress' => 'Em curso', 'resolved' => 'Resolvida', 'closed' => 'Fechada'];

        return collect($record->workflow_history ?? [])->sortBy('revision')->map(fn (array $entry): array => [
            'revision' => (string) $entry['revision'],
            'action' => $actions[$entry['action']] ?? $entry['action'],
            'from' => $states[$entry['from']] ?? $entry['from'],
            'to' => $states[$entry['to']] ?? $entry['to'],
            'actor' => $entry['actor_name'],
            'at' => CarbonImmutable::parse($entry['at'])->setTimezone(config('app.timezone'))->format('d/m/Y H:i:s'),
            'evidence' => $entry['evidence'],
        ])->values()->all();
    }
}
