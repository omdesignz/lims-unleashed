<?php

namespace App\Support;

use App\Models\ControlChart;
use App\Models\ControlChartPoint;
use App\Settings\GeneralSettings;
use Illuminate\Http\Response;
use PDF;

/**
 * A control chart as people read it: its points judged against the limits,
 * the statistics of the included points, the drawing, and the controlled
 * document (PDF) that keeps it as a quality record.
 */
class ControlChartDocument
{
    /** @var list<array<string, mixed>>|null */
    private ?array $points = null;

    public function __construct(private readonly ControlChart $chart) {}

    /**
     * Every point in order of measurement, with its state and the rules it breaks.
     *
     * @return list<array<string, mixed>>
     */
    public function points(): array
    {
        if ($this->points !== null) {
            return $this->points;
        }

        $points = $this->chart->points->values();
        $evaluations = ControlChartEvaluation::evaluate(
            $points->map(fn (ControlChartPoint $point): array => ['value' => $point->value, 'excluded' => $point->excluded])->all(),
            $this->chart->limits()
        );

        return $this->points = $points->map(function (ControlChartPoint $point, int $index) use ($evaluations): array {
            $evaluation = $evaluations[$index];

            return [
                'id' => $point->id,
                'sequence' => $index + 1,
                'measured_at' => $point->measured_at?->format('d/m/Y'),
                'measured_at_iso' => $point->measured_at?->toDateString(),
                'value' => $point->value,
                'value_label' => self::number($point->value),
                'replicate_a' => $point->replicate_a,
                'replicate_b' => $point->replicate_b,
                'run_reference' => $point->run_reference,
                'notes' => $point->notes,
                'excluded' => $point->excluded,
                'exclusion_reason' => $point->exclusion_reason,
                'corrective_action' => $point->corrective_action,
                'corrective_action_at' => $point->corrective_action_at?->format('d/m/Y H:i'),
                'corrective_action_by' => $point->correctiveActionBy?->name,
                'recorded_by' => $point->recordedBy?->name,
                'from_result' => $point->result_id !== null,
                'sample_entry_code' => $point->sampleEntry?->code,
                'sample_entry_url' => $point->sample_entry_id ? route('vap_samples.show', $point->sample_entry_id) : null,
                'state' => $evaluation['state'],
                'state_label' => ControlChartEvaluation::STATES[$evaluation['state']],
                'rules' => $evaluation['rules'],
                'rule_labels' => array_map(fn (string $rule): string => ControlChartEvaluation::RULES[$rule], $evaluation['rules']),
                'zone' => $evaluation['zone'],
                'needs_action' => $evaluation['state'] === ControlChartEvaluation::OUT_OF_CONTROL && blank($point->corrective_action),
            ];
        })->all();
    }

    /**
     * What the included points show, beside what the limits assume.
     *
     * @return array{count: int, mean: ?float, standard_deviation: ?float, minimum: ?float, maximum: ?float, within_warning: ?float, out_of_control: int, warnings: int, open_actions: int, excluded: int}
     */
    public function statistics(): array
    {
        $points = collect($this->points());
        $included = $points->where('excluded', false);
        $values = $included->pluck('value')->map(fn (mixed $value): float => (float) $value);
        $count = $values->count();
        $mean = $count > 0 ? $values->avg() : null;
        $judged = $included->whereIn('state', [ControlChartEvaluation::IN_CONTROL, ControlChartEvaluation::WARNING, ControlChartEvaluation::OUT_OF_CONTROL]);

        return [
            'count' => $count,
            'mean' => $mean,
            'standard_deviation' => $count > 1 ? sqrt($values->sum(fn (float $value): float => ($value - $mean) ** 2) / ($count - 1)) : null,
            'minimum' => $count > 0 ? $values->min() : null,
            'maximum' => $count > 0 ? $values->max() : null,
            'within_warning' => $judged->isNotEmpty() ? round(100 * $judged->whereNull('zone')->count() / $judged->count(), 1) : null,
            'out_of_control' => $included->where('state', ControlChartEvaluation::OUT_OF_CONTROL)->count(),
            'warnings' => $included->where('state', ControlChartEvaluation::WARNING)->count(),
            'open_actions' => $points->where('needs_action', true)->count(),
            'excluded' => $points->where('excluded', true)->count(),
        ];
    }

    /**
     * The chart drawn as SVG with plain shapes that mPDF and Chrome both draw:
     * the centre line, warning and action limits, and the included points,
     * those out of control filled red and those in warning amber.
     */
    public function svg(int $width = 760, int $height = 300): string
    {
        $limits = $this->chart->limits();
        $points = array_values(array_filter($this->points(), fn (array $point): bool => ! $point['excluded']));
        $left = 64;
        $right = 92;
        $top = 14;
        $bottom = 30;
        $plotWidth = $width - $left - $right;
        $plotHeight = $height - $top - $bottom;

        $values = array_map(fn (array $point): float => (float) $point['value'], $points);
        $lines = $limits === null ? [] : array_filter([
            ['LSA', $limits['upper_action'], '#b42332', '4,3'],
            ['LSV', $limits['upper_warning'], '#8a5200', '4,3'],
            ['LC', $limits['centre'], '#111827', ''],
            ['LIV', $limits['lower_warning'], '#8a5200', '4,3'],
            ['LIA', $limits['lower_action'], '#b42332', '4,3'],
        ], fn (array $line): bool => $line[1] !== null);
        $span = [...$values, ...array_map(fn (array $line): float => (float) $line[1], $lines)];

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'" font-family="DejaVu Sans, Arial, sans-serif">';
        $svg .= '<rect x="0" y="0" width="'.$width.'" height="'.$height.'" fill="#ffffff"/>';

        if ($span === []) {
            return $svg.'<text x="'.($width / 2).'" y="'.($height / 2).'" font-size="12" text-anchor="middle" fill="#4b5563">Sem pontos registados.</text></svg>';
        }

        $low = min($span);
        $high = max($span);
        $pad = ($high - $low) * 0.08 ?: (abs($high) * 0.1 ?: 1);
        $low -= $pad;
        $high += $pad;
        if ($this->chart->isRange()) {
            $low = 0.0;
        }
        $y = fn (float $value): float => round($top + $plotHeight - (($value - $low) / ($high - $low)) * $plotHeight, 2);
        $x = fn (int $index): float => round($left + (count($points) > 1 ? $index * $plotWidth / (count($points) - 1) : $plotWidth / 2), 2);

        $svg .= '<line x1="'.$left.'" y1="'.$top.'" x2="'.$left.'" y2="'.($top + $plotHeight).'" stroke="#9ca3af" stroke-width="1"/>';
        $svg .= '<line x1="'.$left.'" y1="'.($top + $plotHeight).'" x2="'.($left + $plotWidth).'" y2="'.($top + $plotHeight).'" stroke="#9ca3af" stroke-width="1"/>';

        foreach (self::ticks($low, $high) as $tick) {
            $svg .= '<line x1="'.($left - 3).'" y1="'.$y($tick).'" x2="'.$left.'" y2="'.$y($tick).'" stroke="#9ca3af" stroke-width="1"/>';
            $svg .= '<text x="'.($left - 6).'" y="'.($y($tick) + 3).'" font-size="9" text-anchor="end" fill="#4b5563">'.e(self::number($tick)).'</text>';
        }

        foreach ($lines as [$label, $value, $colour, $dash]) {
            $lineY = $y((float) $value);
            $svg .= '<line x1="'.$left.'" y1="'.$lineY.'" x2="'.($left + $plotWidth).'" y2="'.$lineY.'" stroke="'.$colour.'" stroke-width="1"'.($dash !== '' ? ' stroke-dasharray="'.$dash.'"' : '').'/>';
            $svg .= '<text x="'.($left + $plotWidth + 6).'" y="'.($lineY + 3).'" font-size="9" fill="'.$colour.'">'.$label.' '.e(self::number((float) $value)).'</text>';
        }

        if (count($points) > 1) {
            $path = implode(' ', array_map(fn (int $index): string => $x($index).','.$y($values[$index]), array_keys($points)));
            $svg .= '<polyline points="'.$path.'" fill="none" stroke="#1d4ed8" stroke-width="1.2"/>';
        }

        $labelEvery = max(1, (int) ceil(count($points) / 12));
        foreach ($points as $index => $point) {
            $fill = match ($point['state']) {
                ControlChartEvaluation::OUT_OF_CONTROL => '#b42332',
                ControlChartEvaluation::WARNING => '#d97706',
                default => '#1d4ed8',
            };
            $radius = $point['state'] === ControlChartEvaluation::OUT_OF_CONTROL ? 4 : 3;
            $svg .= '<circle cx="'.$x($index).'" cy="'.$y($values[$index]).'" r="'.$radius.'" fill="'.$fill.'" stroke="#ffffff" stroke-width="0.8"/>';

            if ($index % $labelEvery === 0 || $index === count($points) - 1) {
                $svg .= '<text x="'.$x($index).'" y="'.($top + $plotHeight + 14).'" font-size="8.5" text-anchor="middle" fill="#4b5563">'.$point['sequence'].'</text>';
            }
        }

        return $svg.'</svg>';
    }

    public function download(): Response
    {
        $pdf = PDF::loadView('PDFs.control-chart', [
            'chart' => $this->chart,
            'document' => $this,
            'settings' => app(GeneralSettings::class),
        ]);

        return PdfResponse::download($pdf, 'carta-de-controlo-'.$this->chart->id.'-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * Round axis values between two bounds: steps of 1, 2 or 5 times a power
     * of ten, about five of them.
     *
     * @return list<float>
     */
    private static function ticks(float $low, float $high): array
    {
        $raw = ($high - $low) / 5;
        $power = 10 ** floor(log10($raw));
        $step = collect([1, 2, 5, 10])->map(fn (int $factor): float => $factor * $power)->first(fn (float $candidate): bool => $candidate >= $raw);
        $ticks = [];

        for ($tick = ceil($low / $step) * $step; $tick <= $high + $step * 1e-9; $tick += $step) {
            $ticks[] = round($tick, 10);
        }

        return $ticks;
    }

    /** A measurement in Portuguese notation, with the decimals its size needs. */
    public static function number(?float $value): string
    {
        if ($value === null) {
            return ControlledDocument::NOT_RECORDED;
        }

        $magnitude = abs($value);
        $decimals = match (true) {
            $magnitude >= 1000 => 1,
            $magnitude >= 100 => 2,
            $magnitude >= 1 => 3,
            default => 4,
        };
        $text = number_format($value, $decimals, ',', ' ');

        return str_contains($text, ',') ? rtrim(rtrim($text, '0'), ',') : $text;
    }
}
