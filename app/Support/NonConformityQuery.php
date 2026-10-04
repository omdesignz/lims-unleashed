<?php

namespace App\Support;

use App\Models\VAPNonConformity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class NonConformityQuery
{
    public const EXPORT_LIMIT = 5000;

    /** @param array<string, mixed> $filters
     * @return Builder<VAPNonConformity>
     */
    public function query(int $labId, array $filters): Builder
    {
        $query = VAPNonConformity::query()->where('lab_id', $labId)
            ->when($filters['archived'] ?? false, fn (Builder $query) => $query->onlyTrashed());
        foreach (['status', 'severity', 'category'] as $field) {
            if (filled($filters[$field] ?? null)) {
                $query->where($field, $filters[$field]);
            }
        }
        if (filled($filters['search'] ?? null)) {
            $term = '%'.addcslashes($filters['search'], '\\%_').'%';
            $query->where(fn (Builder $query) => $query->where('nc_number', 'ilike', $term)
                ->orWhere('title', 'ilike', $term)->orWhere('description', 'ilike', $term));
        }
        foreach (['start_date' => '>=', 'end_date' => '<='] as $field => $operator) {
            if (filled($filters[$field] ?? null)) {
                $query->whereDate('reported_at', $operator, $filters[$field]);
            }
        }

        return $query;
    }

    /** @param array<string, mixed> $filters
     * @return Collection<int, VAPNonConformity>
     */
    public function exportRows(int $labId, array $filters): Collection
    {
        $rows = $this->query($labId, $filters)->with(['lab:id,name', 'department:id,name'])
            ->orderByDesc('created_at')->orderByDesc('id')->limit(self::EXPORT_LIMIT + 1)->get();
        if ($rows->count() > self::EXPORT_LIMIT) {
            throw ValidationException::withMessages(['export' => 'Limite de 5.000 registos por exportação. Restrinja os filtros.']);
        }

        return $rows;
    }

    /** @param Builder<VAPNonConformity> $query */
    public function stats(Builder $query): array
    {
        $counts = (clone $query)->toBase()->selectRaw("count(*) as total,
            count(case when status in ('opened', 'in_progress') then 1 end) as open,
            count(case when severity = 'critical' then 1 end) as critical,
            count(case when status in ('opened', 'in_progress') and due_date < ? then 1 end) as overdue,
            count(case when status in ('opened', 'in_progress') or severity = 'critical' then 1 end) as attention", [now()])->first();

        return array_map('intval', (array) $counts);
    }

    /** @param Builder<VAPNonConformity> $query */
    public function charts(Builder $query): array
    {
        $charts = [];
        foreach ([
            'status' => ['opened' => 'Aberta', 'in_progress' => 'Em progresso', 'resolved' => 'Resolvida', 'closed' => 'Fechada'],
            'severity' => ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'critical' => 'Crítica'],
            'category' => ['quality' => 'Qualidade', 'safety' => 'Segurança', 'environmental' => 'Ambiental', 'regulatory' => 'Regulatório', 'other' => 'Outro'],
        ] as $column => $labels) {
            $counts = (clone $query)->selectRaw("{$column}, count(*) as aggregate")->groupBy($column)->pluck('aggregate', $column);
            $charts[$column] = ['labels' => array_values($labels),
                'series' => array_map(fn (string $key): int => (int) ($counts[$key] ?? 0), array_keys($labels))];
        }
        $months = collect(range(5, 0))->map(fn (int $ago) => now()->startOfMonth()->subMonths($ago));
        $counts = (clone $query)->whereBetween('reported_at', [$months->first(), $months->last()->copy()->endOfMonth()])
            ->selectRaw("to_char(reported_at, 'YYYY-MM') as month_key, count(*) as aggregate")
            ->groupByRaw("to_char(reported_at, 'YYYY-MM')")->pluck('aggregate', 'month_key');
        $charts['trend'] = ['categories' => $months->map(fn ($month) => $month->translatedFormat('M Y'))->all(),
            'series' => [['name' => 'Reportadas', 'data' => $months->map(fn ($month): int => (int) ($counts[$month->format('Y-m')] ?? 0))->all()]]];

        return $charts;
    }
}
