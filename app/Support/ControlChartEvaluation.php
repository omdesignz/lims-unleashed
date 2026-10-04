<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Limits and interpretation of a Shewhart control chart, as used for the
 * internal quality control of tests (ISO/IEC 17025:2017, 7.7.1; ISO 7870-2;
 * Nordtest TR 569).
 *
 * - Mean chart (X): centre line CL and standard deviation s; warning limits
 *   CL ± 2s, action limits CL ± 3s.
 * - Range chart (R) of duplicates: centre line R̄; upper warning limit
 *   2.512·R̄ and upper action limit 3.267·R̄ (n = 2: 2.833·s and 3.686·s with
 *   R̄ = 1.128·s). A range has no lower limits.
 *
 * Each included point, in order of measurement, is in control, in warning
 * (between warning and action limits) or out of control when it breaks one
 * of these rules:
 *
 * - `action`: the point lies outside an action limit;
 * - `two_of_three`: two of three successive points lie outside the same
 *   warning limit;
 * - `trend`: seven successive points steadily increase or decrease;
 * - `shift`: ten of eleven successive points lie on the same side of the
 *   centre line.
 *
 * Excluded points are listed but never enter a rule or a computed limit.
 */
class ControlChartEvaluation
{
    public const MEAN = 'mean';

    public const RANGE = 'range';

    public const TYPES = [self::MEAN, self::RANGE];

    /** n = 2: upper warning and action limits as multiples of the mean range. */
    public const RANGE_WARNING_FACTOR = 2.512;

    public const RANGE_ACTION_FACTOR = 3.267;

    /** Fewest included points from which limits may be computed. */
    public const MINIMUM_POINTS_FOR_LIMITS = 10;

    /** Points recommended before limits computed from the data are relied on. */
    public const RECOMMENDED_POINTS_FOR_LIMITS = 20;

    public const TREND_LENGTH = 7;

    public const SHIFT_WINDOW = 11;

    public const SHIFT_COUNT = 10;

    public const IN_CONTROL = 'in_control';

    public const WARNING = 'warning';

    public const OUT_OF_CONTROL = 'out_of_control';

    public const EXCLUDED = 'excluded';

    public const NOT_EVALUATED = 'not_evaluated';

    /** @var array<string, string> */
    public const RULES = [
        'action' => 'Ponto fora dos limites de acção.',
        'two_of_three' => 'Dois de três pontos sucessivos fora do mesmo limite de aviso.',
        'trend' => 'Sete pontos sucessivos sempre a subir ou sempre a descer.',
        'shift' => 'Dez de onze pontos sucessivos do mesmo lado da linha central.',
    ];

    /** @var array<string, string> */
    public const STATES = [
        self::IN_CONTROL => 'Sob controlo',
        self::WARNING => 'Aviso',
        self::OUT_OF_CONTROL => 'Fora de controlo',
        self::EXCLUDED => 'Excluído',
        self::NOT_EVALUATED => 'Sem limites',
    ];

    /**
     * The chart's lines, or null while its limits are not set.
     *
     * @return array{centre: float, upper_warning: float, upper_action: float, lower_warning: ?float, lower_action: ?float}|null
     */
    public static function limits(string $type, mixed $centreLine, mixed $standardDeviation = null): ?array
    {
        self::assertType($type);

        if (! is_numeric($centreLine)) {
            return null;
        }

        $centre = (float) $centreLine;

        if ($type === self::RANGE) {
            return $centre > 0 ? [
                'centre' => $centre,
                'upper_warning' => self::RANGE_WARNING_FACTOR * $centre,
                'upper_action' => self::RANGE_ACTION_FACTOR * $centre,
                'lower_warning' => null,
                'lower_action' => null,
            ] : null;
        }

        if (! is_numeric($standardDeviation) || (float) $standardDeviation <= 0) {
            return null;
        }

        $s = (float) $standardDeviation;

        return [
            'centre' => $centre,
            'upper_warning' => $centre + 2 * $s,
            'upper_action' => $centre + 3 * $s,
            'lower_warning' => $centre - 2 * $s,
            'lower_action' => $centre - 3 * $s,
        ];
    }

    /**
     * Limits computed from the included values: the mean and the sample
     * standard deviation (mean chart) or the mean range (range chart).
     *
     * @param  array<int, float|int|string>  $values
     * @return array{centre_line: float, standard_deviation: ?float, point_count: int}
     */
    public static function computeLimits(string $type, array $values): array
    {
        self::assertType($type);
        $values = array_values(array_map('floatval', array_filter($values, 'is_numeric')));
        $count = count($values);

        if ($count < self::MINIMUM_POINTS_FOR_LIMITS) {
            throw new InvalidArgumentException('São necessários pelo menos '.self::MINIMUM_POINTS_FOR_LIMITS.' pontos incluídos para calcular os limites.');
        }

        $mean = array_sum($values) / $count;

        if ($type === self::RANGE) {
            return ['centre_line' => $mean, 'standard_deviation' => null, 'point_count' => $count];
        }

        $variance = array_sum(array_map(fn (float $value): float => ($value - $mean) ** 2, $values)) / ($count - 1);

        if ($variance <= 0) {
            throw new InvalidArgumentException('Os pontos não têm dispersão: o desvio-padrão seria zero.');
        }

        return ['centre_line' => $mean, 'standard_deviation' => sqrt($variance), 'point_count' => $count];
    }

    /**
     * Each point's state and the rules it breaks, in the order given (the
     * order of measurement).
     *
     * @param  array<int, array{value: float|int|string, excluded?: bool}>  $points
     * @param  array{centre: float, upper_warning: float, upper_action: float, lower_warning: ?float, lower_action: ?float}|null  $limits
     * @return array<int, array{state: string, rules: list<string>, zone: ?string}>
     */
    public static function evaluate(array $points, ?array $limits): array
    {
        $evaluations = [];
        /** @var list<array{value: float, zone: ?string, side: int}> $history */
        $history = [];

        foreach ($points as $key => $point) {
            if (! empty($point['excluded'])) {
                $evaluations[$key] = ['state' => self::EXCLUDED, 'rules' => [], 'zone' => null];

                continue;
            }

            if ($limits === null) {
                $evaluations[$key] = ['state' => self::NOT_EVALUATED, 'rules' => [], 'zone' => null];

                continue;
            }

            $value = (float) $point['value'];
            $zone = self::zone($value, $limits);
            $history[] = ['value' => $value, 'zone' => $zone, 'side' => $value <=> $limits['centre']];
            $rules = self::brokenRules($history);

            $evaluations[$key] = [
                'state' => $rules !== [] ? self::OUT_OF_CONTROL : ($zone !== null ? self::WARNING : self::IN_CONTROL),
                'rules' => $rules,
                'zone' => $zone,
            ];
        }

        return $evaluations;
    }

    /**
     * Where a value lies: null inside the warning limits, otherwise
     * `upper_warning`, `upper_action`, `lower_warning` or `lower_action`.
     *
     * @param  array{centre: float, upper_warning: float, upper_action: float, lower_warning: ?float, lower_action: ?float}  $limits
     */
    private static function zone(float $value, array $limits): ?string
    {
        return match (true) {
            $value > $limits['upper_action'] => 'upper_action',
            $value > $limits['upper_warning'] => 'upper_warning',
            $limits['lower_action'] !== null && $value < $limits['lower_action'] => 'lower_action',
            $limits['lower_warning'] !== null && $value < $limits['lower_warning'] => 'lower_warning',
            default => null,
        };
    }

    /**
     * The rules the latest point of the history breaks.
     *
     * @param  list<array{value: float, zone: ?string, side: int}>  $history
     * @return list<string>
     */
    private static function brokenRules(array $history): array
    {
        $current = $history[array_key_last($history)];
        $rules = [];

        if (in_array($current['zone'], ['upper_action', 'lower_action'], true)) {
            $rules[] = 'action';
        }

        if ($current['zone'] !== null) {
            $side = str_starts_with($current['zone'], 'upper') ? 'upper' : 'lower';
            $beyond = array_filter(array_slice($history, -3), fn (array $point): bool => $point['zone'] !== null && str_starts_with($point['zone'], $side));

            if (count($beyond) >= 2) {
                $rules[] = 'two_of_three';
            }
        }

        $run = array_slice($history, -self::TREND_LENGTH);
        if (count($run) === self::TREND_LENGTH) {
            $rising = true;
            $falling = true;
            for ($index = 1; $index < count($run); $index++) {
                $rising = $rising && $run[$index]['value'] > $run[$index - 1]['value'];
                $falling = $falling && $run[$index]['value'] < $run[$index - 1]['value'];
            }

            if ($rising || $falling) {
                $rules[] = 'trend';
            }
        }

        $window = array_slice($history, -self::SHIFT_WINDOW);
        if ($current['side'] !== 0 && count($window) >= self::SHIFT_COUNT) {
            $sameSide = count(array_filter($window, fn (array $point): bool => $point['side'] === $current['side']));

            if ($sameSide >= self::SHIFT_COUNT) {
                $rules[] = 'shift';
            }
        }

        return $rules;
    }

    private static function assertType(string $type): void
    {
        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Tipo de carta de controlo desconhecido: '.$type);
        }
    }
}
