<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\ProficiencyTest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProficiencyTestNotifier
{
    public function __construct(private readonly NotificationTemplateService $templates) {}

    public function notifyCreated(ProficiencyTest $test): void
    {
        $this->send(
            $test,
            sprintf('Planeado com o provedor %s.', $test->provider_name),
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

        $message = $becameUnsatisfactory
            ? sprintf('%s teve resultado insatisfatório e deve gerar análise de causa/acção corretiva.', $test->round_reference)
            : sprintf('%s mudou para estado %s com resultado %s.', $test->round_reference, $test->status, $test->outcome);

        $this->send(
            $test,
            $message,
            'updated:'.$test->id.':'.$test->updated_at?->format('YmdHi')
        );
    }

    public function notifyDueSoon(ProficiencyTest $test): void
    {
        $this->send(
            $test,
            sprintf('%s deve ser acompanhado até %s.', $test->round_reference, $test->deadlineDate()?->format('d/m/Y') ?? 'data em aberto'),
            'due-soon:'.$test->id.':'.now()->format('Ymd'),
            now()->addHours(18)
        );
    }

    public function notifyOverdue(ProficiencyTest $test): void
    {
        $this->send(
            $test,
            sprintf('%s ultrapassou o prazo e requer revisão operacional.', $test->round_reference),
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
            $hasCriticalResult
                ? sprintf('%s tem resultados insatisfatórios e requer acção corretiva documentada.', $test->round_reference)
                : sprintf('%s recebeu novos resultados e está pronto para revisão técnica.', $test->round_reference),
            'results-updated:'.$test->id.':'.$test->updated_at?->format('YmdHi')
        );
    }

    private function recipients(int $labId): Collection
    {
        return $this->labUsers($labId)
            ->role('admin')
            ->get()
            ->concat($this->usersWithPermission('view_proficiency_tests', $labId))
            ->concat($this->usersWithPermission('view_analysis', $labId))
            ->unique('id')
            ->values();
    }

    private function usersWithPermission(string $permission, int $labId): Collection
    {
        if (! Permission::query()->where('name', $permission)->exists()) {
            return collect();
        }

        return $this->labUsers($labId)
            ->permission($permission)
            ->get();
    }

    /** @return Builder<User> */
    private function labUsers(int $labId): Builder
    {
        return User::query()
            ->whereIn('users.id', DB::table('lab_user')->where('lab_id', $labId)->select('user_id'))
            ->where('is_active', true)
            ->whereNotNull('email_verified_at');
    }

    private function send(
        ProficiencyTest $test,
        string $detail,
        string $cacheKey,
        mixed $ttl = null,
    ): void {
        if (! $test->lab_id) {
            return;
        }

        $targets = $this->recipients((int) $test->lab_id);

        if ($targets->isEmpty()) {
            return;
        }

        if (! Cache::add('proficiency-test-notification:'.$test->lab_id.':'.$cacheKey, true, $ttl ?? now()->addHours(6))) {
            return;
        }

        $this->templates->notify($targets, 'quality.proficiency_test.updated', [
            'lab_id' => (int) $test->lab_id,
            'document_number' => $test->round_reference,
            'status' => $test->status,
            'outcome' => $test->outcome ?: 'pendente',
            'detail' => $detail,
            'document_url' => route('proficiency_tests.index'),
        ]);
    }
}
