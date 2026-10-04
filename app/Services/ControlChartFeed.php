<?php

namespace App\Services;

use App\Models\ControlChart;
use App\Models\Result;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Brings the approved results of quality control samples onto their control
 * charts.
 *
 * A chart is fed when it names a parameter and a control material: every
 * approved, numeric result for that parameter of a sample of that material,
 * received in the chart's laboratory, becomes a point. A mean chart takes
 * each result; a range chart takes the first two approved results of the
 * same sample (the analysis and its repetition) as duplicates.
 *
 * Each point keeps the result it came from, so feeding is idempotent: a
 * result is plotted once, and a point excluded later is not brought back.
 */
class ControlChartFeed
{
    /**
     * Feeds every active chart of a laboratory that a control material's
     * results can reach.
     *
     * @return int points created
     */
    public function feedMaterial(int $labId, int $productId): int
    {
        return ControlChart::query()
            ->where('lab_id', $labId)
            ->where('control_product_id', $productId)
            ->where('status', 'active')
            ->whereNotNull('parameter_id')
            ->get()
            ->sum(fn (ControlChart $chart): int => $this->feed($chart));
    }

    /**
     * @return int points created
     */
    public function feed(ControlChart $chart): int
    {
        if ($chart->status !== 'active' || ! $chart->parameter_id || ! $chart->control_product_id) {
            return 0;
        }

        return DB::transaction(function () use ($chart): int {
            $chart = ControlChart::query()->lockForUpdate()->findOrFail($chart->id);
            $plotted = $chart->points()->whereNotNull('result_id')->pluck('result_id')
                ->merge($chart->points()->whereNotNull('paired_result_id')->pluck('paired_result_id'))
                ->map(fn (mixed $id): int => (int) $id)->all();
            $results = $this->approvedResults($chart)->reject(fn (object $result): bool => in_array((int) $result->id, $plotted, true));

            $points = $chart->isRange() ? $this->duplicatePoints($chart, $results) : $this->singlePoints($results);

            foreach ($points as $point) {
                $chart->points()->create($point);
            }

            return count($points);
        });
    }

    /**
     * Approved results of the chart's parameter on samples of its control
     * material in its laboratory, oldest first.
     *
     * @return Collection<int, object>
     */
    private function approvedResults(ControlChart $chart): Collection
    {
        return Result::query()
            ->toBase()
            ->join('sample_entries', 'sample_entries.collection_product_id', '=', 'results.collection_id')
            ->join('collection_product', 'collection_product.id', '=', 'results.collection_id')
            ->leftJoin('lab_codes', 'lab_codes.id', '=', 'results.code_id')
            ->where('results.parameter_id', $chart->parameter_id)
            ->whereNotNull('results.approved_date')
            ->whereNull('results.deleted_at')
            ->whereNull('sample_entries.deleted_at')
            ->where('sample_entries.lab_id', $chart->lab_id)
            ->where('collection_product.product_id', $chart->control_product_id)
            ->orderBy('results.approved_date')
            ->orderBy('results.id')
            ->get([
                'results.id', 'results.code_id', 'results.approved_value', 'results.approved_by_id',
                'results.inserted_date', 'results.approved_date', 'results.code_label',
                'sample_entries.id as sample_entry_id', 'sample_entries.code as sample_entry_code', 'lab_codes.code as lab_code',
            ])
            ->filter(fn (object $result): bool => $this->number($result->approved_value) !== null)
            ->values();
    }

    /**
     * @param  Collection<int, object>  $results
     * @return list<array<string, mixed>>
     */
    private function singlePoints(Collection $results): array
    {
        return $results->map(fn (object $result): array => [
            ...$this->provenance($result),
            'value' => $this->number($result->approved_value),
            'result_id' => $result->id,
        ])->values()->all();
    }

    /**
     * The first two approved results of each sample, as duplicates.
     *
     * @param  Collection<int, object>  $results
     * @return list<array<string, mixed>>
     */
    private function duplicatePoints(ControlChart $chart, Collection $results): array
    {
        // A sample whose first result is already plotted has its pair already.
        $plottedSamples = $chart->points()->whereNotNull('paired_result_id')->pluck('sample_entry_id')->filter()->all();

        return $results
            ->reject(fn (object $result): bool => in_array($result->sample_entry_id, $plottedSamples))
            ->groupBy('code_id')
            ->filter(fn (Collection $pair): bool => $pair->count() >= 2)
            ->map(function (Collection $pair): array {
                [$first, $second] = [$pair->get(0), $pair->get(1)];
                $a = $this->number($first->approved_value);
                $b = $this->number($second->approved_value);

                return [
                    ...$this->provenance($second),
                    'value' => abs($a - $b),
                    'replicate_a' => $a,
                    'replicate_b' => $b,
                    'result_id' => $first->id,
                    'paired_result_id' => $second->id,
                ];
            })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function provenance(object $result): array
    {
        $code = $result->lab_code ?: $result->code_label ?: $result->sample_entry_code;

        return [
            'measured_at' => $result->inserted_date ?? $result->approved_date,
            'run_reference' => $code,
            'notes' => 'Resultado aprovado da amostra de controlo '.($result->sample_entry_code ?: $code).'.',
            'recorded_by_id' => $result->approved_by_id,
            'sample_entry_id' => $result->sample_entry_id,
        ];
    }

    private function number(mixed $value): ?float
    {
        $text = str_replace([' ', "\u{00A0}"], '', trim((string) $value));

        if (str_contains($text, ',') && ! str_contains($text, '.')) {
            $text = str_replace(',', '.', $text);
        }

        return is_numeric($text) && abs((float) $text) <= 1e9 ? (float) $text : null;
    }

    /** Whether approved results reach the chart on their own. */
    public static function feedsFromResults(ControlChart $chart): bool
    {
        return $chart->parameter_id !== null && $chart->control_product_id !== null;
    }
}
