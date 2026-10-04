<?php

namespace App\Metrics;

use App\Models\Invoice;
use App\Models\MaintenanceTask;
use App\Models\Occurrence;
use App\Models\User;
use App\Models\VAPNonConformity;
use App\Models\VAPProposal;
use App\Models\VAPSampleEntry;
use App\Services\InventoryCatalogueRead;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * The datasets people may chart on their analytics board.
 *
 * Every dataset, measure and grouping is declared here: a chart definition
 * only names keys from this registry, never columns or SQL. Each dataset reads
 * through the same laboratory scope and permission as its own module, so a
 * custom chart can never show more than the person could open elsewhere.
 */
class ChartDatasets
{
    /** @var array<string, int|null> period key => days back (null = everything) */
    public const PERIODS = ['30d' => 30, '90d' => 90, '12m' => 365, 'all' => null];

    /** @var list<string> */
    public const KINDS = ['column', 'bar', 'line', 'area', 'donut'];

    /** Most categories a grouping shows; the rest fold into "Outros" when the measure adds up. */
    public const CATEGORY_LIMIT = 12;

    /** Most series a breakdown shows (the validated palette's adjacent-safe range). */
    public const SERIES_LIMIT = 6;

    /** Bucket key of the folded "Outros" category. */
    private const OTHERS = "\u{0000}others";

    /** @var array<string, array{label: string, unit: string, trunc: string}> */
    private const TIME_DIMENSIONS = [
        'day' => ['label' => 'Dia', 'unit' => 'day', 'trunc' => 'day'],
        'week' => ['label' => 'Semana', 'unit' => 'week', 'trunc' => 'week'],
        'month' => ['label' => 'Mês', 'unit' => 'month', 'trunc' => 'month'],
    ];

    private const MONTHS = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

    public function __construct(private readonly InventoryCatalogueRead $inventory) {}

    /**
     * Datasets the person may chart, described for the chart builder.
     *
     * @return list<array{key: string, label: string, description: string, measures: list<array{key: string, label: string, format: string, additive: bool, requires: string|null}>, dimensions: list<array{key: string, label: string, type: string}>}>
     */
    public function available(User $user): array
    {
        return collect($this->definitions())
            ->filter(fn (array $dataset): bool => ($dataset['allowed'])($user))
            ->map(fn (array $dataset, string $key): array => [
                'key' => $key,
                'label' => $dataset['label'],
                'description' => $dataset['description'],
                'measures' => collect($dataset['measures'])->map(fn (array $measure, string $measureKey): array => [
                    'key' => $measureKey,
                    'label' => $measure['label'],
                    'format' => $measure['format'],
                    'additive' => $measure['additive'],
                    'requires' => $measure['requires'] ?? null,
                ])->values()->all(),
                'dimensions' => collect(self::TIME_DIMENSIONS)
                    ->map(fn (array $time, string $timeKey): array => ['key' => $timeKey, 'label' => $time['label'], 'type' => 'time'])
                    ->merge(collect($dataset['dimensions'])->map(fn (array $dimension, string $dimensionKey): array => ['key' => $dimensionKey, 'label' => $dimension['label'], 'type' => 'category']))
                    ->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Why a definition cannot be drawn, keyed by field; empty when it can.
     *
     * @param  array{dataset?: mixed, measure?: mixed, dimension?: mixed, split?: mixed, kind?: mixed, period?: mixed}  $definition
     * @return array<string, string>
     */
    public function problems(User $user, array $definition): array
    {
        $datasets = $this->definitions();
        $dataset = $datasets[$definition['dataset'] ?? ''] ?? null;

        if ($dataset === null || ! ($dataset['allowed'])($user)) {
            return ['dataset' => 'Escolha um conjunto de dados a que tenha acesso.'];
        }

        $problems = [];
        $measure = $dataset['measures'][$definition['measure'] ?? ''] ?? null;
        $dimensionKey = (string) ($definition['dimension'] ?? '');
        $isTime = array_key_exists($dimensionKey, self::TIME_DIMENSIONS);
        $split = $definition['split'] ?? null;
        $kind = (string) ($definition['kind'] ?? '');

        if ($measure === null) {
            $problems['measure'] = 'Escolha uma medida deste conjunto de dados.';
        }

        if (! $isTime && ! array_key_exists($dimensionKey, $dataset['dimensions'])) {
            $problems['dimension'] = 'Escolha um agrupamento deste conjunto de dados.';
        }

        if ($measure !== null && ($measure['requires'] ?? null) !== null && $measure['requires'] !== $dimensionKey) {
            $problems['measure'] = $measure['requires_reason'];
        }

        if ($split !== null && $split !== '') {
            if (! array_key_exists($split, $dataset['dimensions'])) {
                $problems['split'] = 'Escolha uma divisão deste conjunto de dados.';
            } elseif ($split === $dimensionKey) {
                $problems['split'] = 'A divisão tem de ser diferente do agrupamento.';
            } elseif ($measure !== null && ! $measure['additive'] && $kind !== 'line') {
                $problems['split'] = 'Médias divididas lêem-se em linhas; escolha o gráfico de linhas ou retire a divisão.';
            }
        }

        if (! in_array($kind, self::KINDS, true)) {
            $problems['kind'] = 'Escolha a forma do gráfico.';
        } elseif (in_array($kind, ['line', 'area'], true) && ! $isTime) {
            $problems['kind'] = 'Linhas e áreas mostram evolução: agrupe por dia, semana ou mês.';
        } elseif ($kind === 'area' && $split !== null && $split !== '') {
            $problems['kind'] = 'Uma área mostra uma só série; use linhas para comparar séries.';
        } elseif ($kind === 'donut' && ($isTime || ($split !== null && $split !== '') || ($measure !== null && ! $measure['additive']))) {
            $problems['kind'] = 'Um donut mostra partes de um total: agrupe por categoria, sem divisão, com uma medida que se soma.';
        }

        if (! array_key_exists((string) ($definition['period'] ?? ''), self::PERIODS)) {
            $problems['period'] = 'Escolha o período.';
        }

        return $problems;
    }

    /**
     * Computes the chart for a valid definition, in the given laboratory.
     *
     * @param  array{dataset: string, measure: string, dimension: string, split?: string|null, kind: string, period: string}  $definition
     * @return array{categories: list<string>, series: list<array{name: string, data: list<float|int|null>}>, format: string, unit: string, dimension_type: string}
     */
    public function run(User $user, int $labId, array $definition, ?CarbonImmutable $now = null): array
    {
        if ($this->problems($user, $definition) !== []) {
            throw new InvalidArgumentException('The chart definition is not valid for this person.');
        }

        $now ??= CarbonImmutable::now();
        $dataset = $this->definitions()[$definition['dataset']];
        $measure = $dataset['measures'][$definition['measure']];
        $time = self::TIME_DIMENSIONS[$definition['dimension']] ?? null;
        $dimension = $time === null ? $dataset['dimensions'][$definition['dimension']] : null;
        $split = filled($definition['split'] ?? null) ? $dataset['dimensions'][$definition['split']] : null;
        $days = self::PERIODS[$definition['period']];
        $start = $days === null ? null : $now->subDays($days - 1)->startOfDay();

        /** @var Builder<Model> $query */
        $query = ($dataset['query'])($user, $labId);
        $query->whereNotNull($dataset['date']);

        if ($start !== null) {
            $query->where($dataset['date'], '>=', $start);
        }

        foreach (array_filter([$dimension['join'] ?? null, $split['join'] ?? null]) as $join) {
            $join($query);
        }

        $bucket = $time !== null ? "date_trunc('{$time['trunc']}', {$dataset['date']})" : $dimension['column'];
        $base = $query->toBase()->reorder();
        $base->columns = null;
        $base->selectRaw("{$bucket} as bucket")
            ->selectRaw(($split !== null ? $split['column'] : "''").' as series')
            ->selectRaw("{$measure['sql']} as value")
            ->groupByRaw($split !== null ? "{$bucket}, {$split['column']}" : $bucket);

        $rows = $base->get()->map(fn (object $row): array => [
            'bucket' => $row->bucket,
            'series' => $split !== null ? $this->label($split, $row->series) : $measure['label'],
            'value' => $row->value === null ? null : (float) $row->value,
        ]);

        [$buckets, $labels] = $time !== null
            ? $this->timeBuckets($rows, $time['unit'], $start, $now)
            : $this->categoryBuckets($rows, $dimension, $measure['additive']);

        $seriesNames = $this->seriesNames($rows, $measure['additive'], $split !== null);
        $foldsSeries = $split !== null && $measure['additive'] && $rows->pluck('series')->unique()->count() > count($seriesNames);

        $shownKeys = array_values(array_filter($buckets, fn (string $key): bool => $key !== self::OTHERS));
        $series = collect($seriesNames)->map(fn (string $name): array => [
            'name' => $name,
            'data' => collect($buckets)->map(fn (string $key): float|int|null => $this->cell($rows, $key, $name, $measure, $time !== null, $shownKeys))->all(),
        ]);

        if ($foldsSeries) {
            $series->push([
                'name' => 'Outros',
                'data' => collect($buckets)->map(fn (string $key): float|int => $this->round(
                    $rows->filter(fn (array $row): bool => ! in_array($row['series'], $seriesNames, true) && $this->inBucket($row, $key, $time !== null, $shownKeys))->sum('value'),
                    $measure['format'],
                ))->all(),
            ]);
        }

        return [
            'categories' => $labels,
            'series' => $series->values()->all(),
            'format' => $measure['format'],
            'unit' => $measure['unit'] ?? '',
            'dimension_type' => $time !== null ? 'time' : 'category',
        ];
    }

    /**
     * @param  Collection<int, array{bucket: mixed, series: string, value: float|null}>  $rows
     * @return array{0: list<string>, 1: list<string>}
     */
    private function timeBuckets(Collection $rows, string $unit, ?CarbonImmutable $start, CarbonImmutable $now): array
    {
        $first = $start ?? $rows->pluck('bucket')->filter()->map(fn (mixed $bucket): CarbonImmutable => CarbonImmutable::parse($bucket))->min();

        if ($first === null) {
            return [[], []];
        }

        // Every step in the period is shown, including the empty ones: a gap is a value.
        $cursor = match ($unit) {
            'month' => $first->startOfMonth(),
            'week' => $first->startOfWeek(),
            default => $first->startOfDay(),
        };
        $keys = [];
        $labels = [];

        while ($cursor <= $now && count($keys) < 400) {
            $keys[] = $cursor->format('Y-m-d');
            $labels[] = match ($unit) {
                'month' => self::MONTHS[$cursor->month - 1].' '.$cursor->format('Y'),
                default => $cursor->format('d/m'),
            };
            $cursor = $cursor->add(1, $unit);
        }

        return [$keys, $labels];
    }

    /**
     * @param  Collection<int, array{bucket: mixed, series: string, value: float|null}>  $rows
     * @param  array{labels?: array<string, string>, column: string}  $dimension
     * @return array{0: list<string>, 1: list<string>}
     */
    private function categoryBuckets(Collection $rows, array $dimension, bool $additive): array
    {
        $totals = $rows->groupBy(fn (array $row): string => $this->bucketKey($row['bucket'], false))
            ->map(fn (Collection $group): float => (float) $group->sum('value'))
            ->sortDesc();
        $kept = $totals->keys()->take(self::CATEGORY_LIMIT)->map(fn (mixed $key): string => (string) $key)->all();
        $labels = array_map(fn (string $key): string => $this->label($dimension, $key === '' ? null : $key), $kept);

        if ($additive && $totals->count() > self::CATEGORY_LIMIT) {
            $kept[] = self::OTHERS;
            $labels[] = 'Outros';
        }

        return [$kept, $labels];
    }

    /**
     * @param  Collection<int, array{bucket: mixed, series: string, value: float|null}>  $rows
     * @return list<string>
     */
    private function seriesNames(Collection $rows, bool $additive, bool $split): array
    {
        $names = $rows->groupBy('series')->map(fn (Collection $group): float => (float) $group->sum('value'))->sortDesc()->keys();

        if (! $split) {
            return $names->take(1)->values()->all() ?: [];
        }

        return $names->take($additive ? self::SERIES_LIMIT - 1 : self::SERIES_LIMIT)->map(fn (mixed $name): string => (string) $name)->values()->all();
    }

    /**
     * @param  Collection<int, array{bucket: mixed, series: string, value: float|null}>  $rows
     * @param  array{format: string, additive: bool}  $measure
     * @param  list<string>  $shownKeys
     */
    private function cell(Collection $rows, string $key, string $series, array $measure, bool $time, array $shownKeys): float|int|null
    {
        $matches = $rows->filter(fn (array $row): bool => $row['series'] === $series && $this->inBucket($row, $key, $time, $shownKeys));

        if ($matches->isEmpty()) {
            // Nothing happened in this step: zero for what adds up, no value for an average.
            return $measure['additive'] ? 0 : null;
        }

        return $this->round($measure['additive'] ? (float) $matches->sum('value') : (float) $matches->avg('value'), $measure['format']);
    }

    /**
     * @param  array{bucket: mixed}  $row
     * @param  list<string>  $shownKeys
     */
    private function inBucket(array $row, string $key, bool $time, array $shownKeys): bool
    {
        $rowKey = $this->bucketKey($row['bucket'], $time);

        return $key === self::OTHERS ? ! in_array($rowKey, $shownKeys, true) : $rowKey === $key;
    }

    private function bucketKey(mixed $bucket, bool $time): string
    {
        if ($time) {
            return CarbonImmutable::parse($bucket)->format('Y-m-d');
        }

        return $bucket === null ? '' : (string) $bucket;
    }

    private function round(float $value, string $format): float|int
    {
        return $format === 'count' ? (int) round($value) : round($value, 2);
    }

    /**
     * @param  array{labels?: array<string, string>|Closure}  $dimension
     */
    private function label(array $dimension, mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'Sem valor';
        }

        $labels = $dimension['labels'] ?? [];

        if ($labels instanceof Closure) {
            return $labels((string) $value);
        }

        return $labels[(string) $value] ?? (string) $value;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function definitions(): array
    {
        $can = fn (string $permission): Closure => fn (User $user): bool => $user->can($permission);
        $inventory = fn (User $user): bool => $user->can('view_inventory') && $this->inventory->allowedTypes($user) !== [];
        $nonConformityLabel = fn (string $group): Closure => function (string $value) use ($group): string {
            $key = "gestlab.general.labels.vap_non_conformities.{$group}.{$value}";
            $label = trans($key);

            return $label === $key ? $value : $label;
        };

        return [
            'samples' => [
                'label' => 'Amostras',
                'description' => 'Entradas de amostra, pela data de recepção.',
                'allowed' => $can('view_samples'),
                'query' => fn (User $user, int $labId): Builder => VAPSampleEntry::query()->where('sample_entries.lab_id', $labId),
                'date' => 'sample_entries.received_at',
                'measures' => [
                    'count' => ['label' => 'Amostras', 'sql' => 'COUNT(*)', 'format' => 'count', 'additive' => true],
                    'turnaround' => ['label' => 'Tempo médio até conclusão', 'sql' => 'AVG(EXTRACT(EPOCH FROM (sample_entries.analysis_end_date - sample_entries.received_at)) / 3600)', 'format' => 'hours', 'additive' => false],
                ],
                'dimensions' => [
                    'status' => ['label' => 'Estado', 'column' => 'sample_entries.status', 'labels' => ['POR_INICIAR' => 'Por iniciar', 'EN_PROGRESO' => 'Em análise', 'EN_PAUSA' => 'Em espera', 'COMPLETADO' => 'Concluída', 'CANCELADO' => 'Cancelada']],
                    'sample_type' => ['label' => 'Tipo de amostra', 'column' => 'sample_entries.sample_type', 'labels' => ['ROTINA' => 'Rotina', 'MATERIA_PRIMA' => 'Matéria-prima', 'RAW_MATERIAL' => 'Matéria-prima', 'PRODUTO_ACABADO' => 'Produto acabado', 'ESTABILIDADE' => 'Estabilidade', 'CONTRAPROVA' => 'Contraprova', 'COUNTER_ANALYSIS' => 'Contra-análise', 'INTERLABORATORIAL' => 'Interlaboratorial', 'RETENCAO' => 'Retenção', 'AGUA' => 'Água']],
                    'customer' => ['label' => 'Cliente', 'column' => 'chart_customers.name', 'join' => fn (Builder $query) => $query->leftJoin('customers as chart_customers', 'chart_customers.id', '=', 'sample_entries.customer_id')],
                ],
            ],
            'nonconformities' => [
                'label' => 'Não conformidades',
                'description' => 'Não conformidades registadas, pela data do relato.',
                'allowed' => $can('view_occurrences'),
                'query' => fn (User $user, int $labId): Builder => VAPNonConformity::query()->where('v_non_conformities.lab_id', $labId),
                'date' => 'v_non_conformities.reported_at',
                'measures' => [
                    'count' => ['label' => 'Não conformidades', 'sql' => 'COUNT(*)', 'format' => 'count', 'additive' => true],
                    'closure' => ['label' => 'Dias médios até ao fecho', 'sql' => 'AVG(EXTRACT(EPOCH FROM (v_non_conformities.closed_at - v_non_conformities.reported_at)) / 86400)', 'format' => 'days', 'additive' => false],
                ],
                'dimensions' => [
                    'status' => ['label' => 'Estado', 'column' => 'v_non_conformities.status', 'labels' => $nonConformityLabel('status')],
                    'severity' => ['label' => 'Severidade', 'column' => 'v_non_conformities.severity', 'labels' => $nonConformityLabel('severity')],
                    'category' => ['label' => 'Categoria', 'column' => 'v_non_conformities.category', 'labels' => $nonConformityLabel('categories')],
                ],
            ],
            'occurrences' => [
                'label' => 'Ocorrências',
                'description' => 'Ocorrências registadas, pela data do relato.',
                'allowed' => $can('view_occurrences'),
                'query' => fn (User $user, int $labId): Builder => Occurrence::query()->where('occurrences.lab_id', $labId),
                'date' => 'occurrences.date_reported',
                'measures' => [
                    'count' => ['label' => 'Ocorrências', 'sql' => 'COUNT(*)', 'format' => 'count', 'additive' => true],
                ],
                'dimensions' => [
                    'status' => ['label' => 'Estado', 'column' => 'chart_occurrence_statuses.name', 'join' => fn (Builder $query) => $query->leftJoin('occurrence_statuses as chart_occurrence_statuses', 'chart_occurrence_statuses.id', '=', 'occurrences.status_id')],
                    'category' => ['label' => 'Categoria', 'column' => 'chart_occurrence_categories.name', 'join' => fn (Builder $query) => $query->leftJoin('occurrence_categories as chart_occurrence_categories', 'chart_occurrence_categories.id', '=', 'occurrences.category_id')],
                    'origin' => ['label' => 'Origem', 'column' => 'chart_occurrence_origins.name', 'join' => fn (Builder $query) => $query->leftJoin('occurrence_origins as chart_occurrence_origins', 'chart_occurrence_origins.id', '=', 'occurrences.origin_id')],
                ],
            ],
            'maintenance' => [
                'label' => 'Manutenção',
                'description' => 'Tarefas de manutenção e calibração, pela data de vencimento.',
                'allowed' => $can('view_maintenance_tasks'),
                'query' => fn (User $user, int $labId): Builder => MaintenanceTask::query()->forLaboratory($labId),
                'date' => 'maintenance_tasks.due_date',
                'measures' => [
                    'count' => ['label' => 'Tarefas', 'sql' => 'COUNT(*)', 'format' => 'count', 'additive' => true],
                    'cost' => ['label' => 'Custo', 'sql' => 'COALESCE(SUM(maintenance_tasks.cost), 0)', 'format' => 'currency', 'additive' => true],
                ],
                'dimensions' => [
                    'state' => ['label' => 'Execução', 'column' => "CASE WHEN maintenance_tasks.is_executed THEN 'executed' WHEN maintenance_tasks.due_date < CURRENT_DATE THEN 'overdue' ELSE 'planned' END", 'labels' => ['executed' => 'Executada', 'overdue' => 'Atrasada', 'planned' => 'Por executar']],
                    'category' => ['label' => 'Categoria', 'column' => 'chart_maintenance_categories.name', 'join' => fn (Builder $query) => $query->leftJoin('maintenance_categories as chart_maintenance_categories', 'chart_maintenance_categories.id', '=', 'maintenance_tasks.category_id')],
                ],
            ],
            'consumption' => [
                'label' => 'Consumo de reagentes',
                'description' => 'Consumos registados e não revertidos, pela data de uso.',
                'allowed' => $inventory,
                'query' => fn (User $user, int $labId): Builder => $this->inventory->consumptions($labId, $user)->unreversed(),
                'date' => 'reagent_consumption.date',
                'measures' => [
                    'count' => ['label' => 'Registos de consumo', 'sql' => 'COUNT(*)', 'format' => 'count', 'additive' => true],
                    // Reagents have their own units: quantities only add up within one reagent.
                    'quantity' => ['label' => 'Quantidade consumida', 'sql' => 'SUM(reagent_consumption.quantity_used)', 'format' => 'decimal', 'additive' => true, 'requires' => 'reagent', 'requires_reason' => 'Cada reagente tem a sua unidade: a quantidade só se soma agrupando por reagente.'],
                ],
                'dimensions' => [
                    'reagent' => ['label' => 'Reagente', 'column' => 'reagent_consumption.reagent_name'],
                    'usage_type' => ['label' => 'Tipo de uso', 'column' => 'reagent_consumption.usage_type'],
                    'used_by' => ['label' => 'Utilizado por', 'column' => 'reagent_consumption.used_by'],
                ],
            ],
            'movements' => [
                'label' => 'Movimentos de inventário',
                'description' => 'Entradas, saídas e acertos de existências, pela data do registo.',
                'allowed' => fn (User $user): bool => $user->can('view_itransactions') && $this->inventory->allowedTypes($user) !== [],
                'query' => fn (User $user, int $labId): Builder => $this->inventory->transactions($labId, $user),
                'date' => 'itransactions.created_at',
                'measures' => [
                    'count' => ['label' => 'Movimentos', 'sql' => 'COUNT(*)', 'format' => 'count', 'additive' => true],
                ],
                'dimensions' => [
                    'type' => ['label' => 'Tipo de movimento', 'column' => 'chart_transaction_types.name', 'join' => fn (Builder $query) => $query->leftJoin('itransaction_types as chart_transaction_types', 'chart_transaction_types.id', '=', 'itransactions.type_id')],
                    'item' => ['label' => 'Artigo', 'column' => 'chart_items.name', 'join' => fn (Builder $query) => $query->leftJoin('i_items as chart_items', 'chart_items.id', '=', 'itransactions.item_id')],
                ],
            ],
            'invoices' => [
                'label' => 'Facturas',
                'description' => 'Facturas emitidas, pela data do documento.',
                'allowed' => $can('view_invoices'),
                'query' => fn (User $user, int $labId): Builder => Invoice::query()->where('invoices.lab_id', $labId),
                'date' => 'invoices.date',
                'measures' => [
                    'count' => ['label' => 'Facturas', 'sql' => 'COUNT(*)', 'format' => 'count', 'additive' => true],
                    'total' => ['label' => 'Total facturado', 'sql' => 'COALESCE(SUM(invoices.total), 0)', 'format' => 'currency', 'additive' => true],
                    'due' => ['label' => 'Valor em dívida', 'sql' => 'COALESCE(SUM(invoices.amount_due), 0)', 'format' => 'currency', 'additive' => true],
                ],
                'dimensions' => [
                    'customer' => ['label' => 'Cliente', 'column' => 'chart_customers.name', 'join' => fn (Builder $query) => $query->leftJoin('customers as chart_customers', 'chart_customers.id', '=', 'invoices.customer_id')],
                ],
            ],
            'proposals' => [
                'label' => 'Propostas',
                'description' => 'Propostas comerciais, pela data de criação.',
                'allowed' => $can('view_proposals'),
                'query' => fn (User $user, int $labId): Builder => VAPProposal::query()->where('proposals.lab_id', $labId),
                'date' => 'proposals.created_at',
                'measures' => [
                    'count' => ['label' => 'Propostas', 'sql' => 'COUNT(*)', 'format' => 'count', 'additive' => true],
                    'total' => ['label' => 'Valor proposto', 'sql' => 'COALESCE(SUM(proposals.total), 0)', 'format' => 'currency', 'additive' => true],
                ],
                'dimensions' => [
                    'status' => ['label' => 'Estado', 'column' => 'proposals.status', 'labels' => ['PENDING' => 'Pendente', 'SENT' => 'Enviada', 'VIEWED' => 'Vista', 'ACCEPTED' => 'Aceite', 'REJECTED' => 'Rejeitada', 'REVISED' => 'Revista', 'EXPIRED' => 'Expirada']],
                    'customer' => ['label' => 'Cliente', 'column' => 'chart_customers.name', 'join' => fn (Builder $query) => $query->leftJoin('customers as chart_customers', 'chart_customers.id', '=', 'proposals.customer_id')],
                ],
            ],
        ];
    }
}
