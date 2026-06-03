<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\ProficiencyTest;
use App\Models\User;
use App\Notifications\ProficiencyTestWorkflowNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class ProficiencyTestNotifier
{
    public function notifyCreated(ProficiencyTest $test): void
    {
        $this->send(
            $test,
            'Novo ensaio de proficiência registado',
            sprintf('%s foi planeado com o provedor %s.', $test->round_reference, $test->provider_name),
            'info',
            'created:'.$test->id
        );
    }

    /**
     * @param  array<string, mixed>  $before
     */
    public function notifyUpdated(ProficiencyTest $test, array $before): void
    {
        $statusChanged = ($before['status'] ?? null) !== $test->status;
        $outcomeChanged = ($before['outcome'] ?? null) !== $test->outcome;
        $becameUnsatisfactory = ($before['outcome'] ?? null) !== 'unsatisfactory' && $test->outcome === 'unsatisfactory';

        if (! $statusChanged && ! $outcomeChanged) {
            return;
        }

        $title = $becameUnsatisfactory
            ? 'Ensaio de proficiência requer ação corretiva'
            : 'Ensaio de proficiência atualizado';

        $message = $becameUnsatisfactory
            ? sprintf('%s teve resultado insatisfatório e deve gerar análise de causa/ação corretiva.', $test->round_reference)
            : sprintf('%s mudou para estado %s com resultado %s.', $test->round_reference, $test->status, $test->outcome);

        $this->send(
            $test,
            $title,
            $message,
            $becameUnsatisfactory ? 'danger' : 'info',
            'updated:'.$test->id.':'.$test->updated_at?->format('YmdHi')
        );
    }

    public function notifyDueSoon(ProficiencyTest $test): void
    {
        $this->send(
            $test,
            'Ensaio de proficiência próximo do prazo',
            sprintf('%s deve ser acompanhado até %s.', $test->round_reference, $test->deadlineDate()?->format('d/m/Y') ?? 'data em aberto'),
            'warning',
            'due-soon:'.$test->id.':'.now()->format('Ymd'),
            now()->addHours(18)
        );
    }

    public function notifyOverdue(ProficiencyTest $test): void
    {
        $this->send(
            $test,
            'Ensaio de proficiência vencido',
            sprintf('%s ultrapassou o prazo e requer revisão operacional.', $test->round_reference),
            'danger',
            'overdue:'.$test->id.':'.now()->format('Ymd'),
            now()->addHours(18)
        );
    }

    public function notifyResultsUpdated(ProficiencyTest $test): void
    {
        $summary = $test->performance_summary ?? $test->calculatePerformanceSummary();
        $hasCriticalResult = (int) ($summary['unsatisfactory'] ?? 0) > 0;

        $this->send(
            $test,
            $hasCriticalResult ? 'Resultado insatisfatório em ensaio de proficiência' : 'Resultados de proficiência atualizados',
            $hasCriticalResult
                ? sprintf('%s tem resultados insatisfatórios e requer ação corretiva documentada.', $test->round_reference)
                : sprintf('%s recebeu novos resultados e está pronto para revisão técnica.', $test->round_reference),
            $hasCriticalResult ? 'danger' : 'info',
            'results-updated:'.$test->id.':'.$test->updated_at?->format('YmdHi')
        );
    }

    private function recipients(): Collection
    {
        return User::query()
            ->role('admin')
            ->whereNotNull('email_verified_at')
            ->get()
            ->concat($this->usersWithPermission('view_proficiency_tests'))
            ->concat($this->usersWithPermission('view_analysis'))
            ->whereNotNull('email_verified_at')
            ->unique('id')
            ->values();
    }

    private function usersWithPermission(string $permission): Collection
    {
        if (! Permission::query()->where('name', $permission)->exists()) {
            return collect();
        }

        return User::query()
            ->permission($permission)
            ->whereNotNull('email_verified_at')
            ->get();
    }

    private function send(
        ProficiencyTest $test,
        string $title,
        string $message,
        string $tone,
        string $cacheKey,
        mixed $ttl = null,
    ): void {
        if (! Cache::add('proficiency-test-notification:'.$cacheKey, true, $ttl ?? now()->addHours(6))) {
            return;
        }

        $targets = $this->recipients();

        if ($targets->isEmpty()) {
            return;
        }

        Notification::send($targets, new ProficiencyTestWorkflowNotification($test, $title, $message, $tone));
    }
}
