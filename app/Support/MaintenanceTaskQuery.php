<?php

namespace App\Support;

use App\Models\MaintenanceTask;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MaintenanceTaskQuery
{
    public const MAX_EXPORT_ROWS = 5000;

    /** @param array<string, mixed> $filters */
    public function query(int $labId, array $filters = []): Builder
    {
        $query = MaintenanceTask::forLaboratory($labId)
            ->when($filters['archived'] ?? false, fn (Builder $query) => $query->onlyTrashed());
        foreach (['task_id' => 'id', 'category_id' => 'category_id', 'equipment_id' => 'equipment_id', 'supplier_id' => 'supplier_id'] as $key => $column) {
            if (filled($filters[$key] ?? null)) {
                $query->where($column, $filters[$key]);
            }
        }
        if (filled($filters['search'] ?? null)) {
            $query->where(fn (Builder $query) => $query->where('name', 'ilike', '%'.$filters['search'].'%')
                ->orWhere('maintenance_task_no', 'ilike', '%'.$filters['search'].'%'));
        }
        foreach (['date_from' => '>=', 'date_to' => '<='] as $key => $operator) {
            if (filled($filters[$key] ?? null)) {
                $query->whereDate('due_date', $operator, $filters[$key]);
            }
        }
        foreach (['cost_min' => '>=', 'cost_max' => '<='] as $key => $operator) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $query->where('cost', $operator, $filters[$key]);
            }
        }
        $today = today()->toDateString();
        $threshold = today()->addDays(30)->toDateString();
        match ($filters['status'] ?? null) {
            'overdue' => $query->where('is_executed', false)->whereDate('due_date', '<', $today),
            'executed' => $query->where('is_executed', true),
            'planned' => $query->where('is_executed', false)->where('is_planned', true),
            'upcoming' => $query->where('is_executed', false)->whereDate('due_date', '>=', $today),
            'due_soon' => $query->where('is_executed', false)->whereDate('due_date', '>=', $today)->whereDate('due_date', '<=', $threshold),
            'scheduled' => $query->where('is_executed', false)->whereDate('due_date', '>', $threshold),
            default => null,
        };

        return $query;
    }

    /** @return array<string, int|float> */
    public function summary(Builder $query): array
    {
        $row = (clone $query)->reorder()->selectRaw('COUNT(*) AS total_tasks,
            COALESCE(SUM(cost), 0) AS total_cost,
            COUNT(*) FILTER (WHERE is_executed) AS executed,
            COUNT(*) FILTER (WHERE NOT is_executed AND is_planned) AS planned,
            COUNT(*) FILTER (WHERE NOT is_executed AND due_date::date < ?) AS overdue,
            COUNT(*) FILTER (WHERE NOT is_executed AND due_date::date BETWEEN ? AND ?) AS due_soon,
            COUNT(*) FILTER (WHERE NOT is_executed AND due_date::date BETWEEN ? AND ?) AS due_this_month', [
            today()->toDateString(), today()->toDateString(), today()->addDays(30)->toDateString(),
            today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString(),
        ])->first();

        return collect($row->getAttributes())->map(fn ($value, string $key): int|float => $key === 'total_cost' ? (float) $value : (int) $value)->all();
    }

    /** @param array<string, mixed> $filters
     * @return Collection<int, MaintenanceTask>
     */
    public function exportRows(int $labId, array $filters): Collection
    {
        $rows = $this->query($labId, $filters)->with(['category', 'equipment', 'supplier'])
            ->orderBy('due_date')->orderBy('id')->limit(self::MAX_EXPORT_ROWS + 1)->get();
        if ($rows->count() > self::MAX_EXPORT_ROWS) {
            throw ValidationException::withMessages(['export' => 'A exportação excede 5000 tarefas. Reduza o intervalo ou aplique filtros.']);
        }

        return $rows;
    }

    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function calendarFilters(array $filters): array
    {
        $start = CarbonImmutable::parse($filters['date_from'] ?? today()->startOfMonth())->startOfDay();
        $end = CarbonImmutable::parse($filters['date_to'] ?? $start->addMonthsNoOverflow(3)->endOfMonth())->startOfDay();
        if ($end->lt($start) || $start->diffInDays($end) > 365) {
            throw ValidationException::withMessages(['date_to' => 'O calendário exige um intervalo ordenado com no máximo 366 dias.']);
        }

        return array_replace($filters, ['date_from' => $start->toDateString(), 'date_to' => $end->toDateString()]);
    }

    /** @param array<string, mixed> $filters
     * @return array<string, array{date: string, day: string, tasks: Collection}>
     */
    public function calendar(int $labId, array $filters): array
    {
        $filters = $this->calendarFilters($filters);
        $tasks = $this->exportRows($labId, $filters)->groupBy(fn (MaintenanceTask $task): string => $task->due_date->toDateString());
        $end = CarbonImmutable::parse($filters['date_to']);
        $calendar = [];
        for ($day = CarbonImmutable::parse($filters['date_from']); $day->lte($end); $day = $day->addDay()) {
            $calendar[$day->toDateString()] = [
                'date' => $day->format('d/m/Y'), 'day' => $day->locale('pt')->translatedFormat('l'),
                'tasks' => $tasks->get($day->toDateString(), collect()),
            ];
        }

        return $calendar;
    }
}
