<?php

namespace Tests\Unit;

use App\Support\ControlChartEvaluation as Chart;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Limits and out-of-control rules of the Shewhart charts used for internal
 * quality control. Mean chart: CL 10, s 1 (warning 8–12, action 7–13).
 */
class ControlChartEvaluationTest extends TestCase
{
    public function test_mean_chart_limits_are_two_and_three_standard_deviations(): void
    {
        $this->assertSame(
            ['centre' => 10.0, 'upper_warning' => 12.0, 'upper_action' => 13.0, 'lower_warning' => 8.0, 'lower_action' => 7.0],
            Chart::limits(Chart::MEAN, '10', '1')
        );
        $this->assertNull(Chart::limits(Chart::MEAN, 10, null));
        $this->assertNull(Chart::limits(Chart::MEAN, 10, 0));
        $this->assertNull(Chart::limits(Chart::MEAN, null, 1));
    }

    public function test_range_chart_limits_have_no_lower_side(): void
    {
        $limits = Chart::limits(Chart::RANGE, 0.4);

        $this->assertSame(0.4, $limits['centre']);
        $this->assertEqualsWithDelta(1.0048, $limits['upper_warning'], 1e-9);
        $this->assertEqualsWithDelta(1.3068, $limits['upper_action'], 1e-9);
        $this->assertNull($limits['lower_warning']);
        $this->assertNull($limits['lower_action']);
        $this->assertNull(Chart::limits(Chart::RANGE, 0));
    }

    public function test_an_unknown_chart_type_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Chart::limits('cusum', 1, 1);
    }

    /**
     * @return array<string, array{list<float>, string, list<string>}>
     */
    public static function sequences(): array
    {
        return [
            'inside the warning limits' => [[10.5, 9.4, 11.9], Chart::IN_CONTROL, []],
            'one point between warning and action' => [[10.1, 9.8, 12.5], Chart::WARNING, []],
            'outside the action limit' => [[10.1, 13.2], Chart::OUT_OF_CONTROL, ['action']],
            'below the lower action limit' => [[10.1, 6.9], Chart::OUT_OF_CONTROL, ['action']],
            'two of three above the same warning limit' => [[12.4, 10.0, 12.2], Chart::OUT_OF_CONTROL, ['two_of_three']],
            'two of three on opposite sides are only a warning' => [[12.4, 10.0, 7.6], Chart::WARNING, []],
            'warning three points back is out of the window' => [[12.4, 10.0, 10.0, 12.2], Chart::WARNING, []],
            'seven points rising' => [[8.5, 8.9, 9.3, 9.7, 10.1, 10.5, 10.9], Chart::OUT_OF_CONTROL, ['trend']],
            'six points rising is not yet a trend' => [[8.9, 9.3, 9.7, 10.1, 10.5, 10.9], Chart::IN_CONTROL, []],
            'a tie breaks the trend' => [[8.5, 8.9, 9.3, 9.3, 10.1, 10.5, 10.9], Chart::IN_CONTROL, []],
            'ten of eleven above the centre line' => [[10.2, 10.4, 9.8, 10.3, 10.1, 10.6, 10.2, 10.5, 10.3, 10.4, 10.2], Chart::OUT_OF_CONTROL, ['shift']],
            'nine of eleven is not a shift' => [[10.2, 10.4, 9.8, 10.3, 9.9, 10.6, 10.2, 10.5, 10.3, 10.4, 10.2], Chart::IN_CONTROL, []],
            'a point on the centre line counts for neither side' => [[10.2, 10.4, 10.3, 10.1, 10.6, 10.2, 10.5, 10.3, 10.4, 10.0], Chart::IN_CONTROL, []],
        ];
    }

    /**
     * @param  list<float>  $values
     * @param  list<string>  $rules
     */
    #[DataProvider('sequences')]
    public function test_the_latest_point_is_judged_on_its_history(array $values, string $state, array $rules): void
    {
        $evaluation = Chart::evaluate(array_map(fn (float $value): array => ['value' => $value], $values), Chart::limits(Chart::MEAN, 10, 1));
        $latest = $evaluation[array_key_last($evaluation)];

        $this->assertSame($state, $latest['state']);
        $this->assertSame($rules, $latest['rules']);
    }

    public function test_a_point_may_break_several_rules_at_once(): void
    {
        $evaluation = Chart::evaluate([['value' => 12.5], ['value' => 13.5]], Chart::limits(Chart::MEAN, 10, 1));

        $this->assertSame(['action', 'two_of_three'], $evaluation[1]['rules']);
        $this->assertSame('upper_action', $evaluation[1]['zone']);
    }

    public function test_excluded_points_are_listed_but_never_enter_a_rule(): void
    {
        $evaluation = Chart::evaluate([
            ['value' => 12.4],
            ['value' => 15.0, 'excluded' => true],
            ['value' => 12.3],
        ], Chart::limits(Chart::MEAN, 10, 1));

        $this->assertSame(Chart::EXCLUDED, $evaluation[1]['state']);
        $this->assertSame(['two_of_three'], $evaluation[2]['rules']);
    }

    public function test_without_limits_nothing_is_judged(): void
    {
        $evaluation = Chart::evaluate([['value' => 1], ['value' => 100]], null);

        $this->assertSame([Chart::NOT_EVALUATED, Chart::NOT_EVALUATED], array_column($evaluation, 'state'));
    }

    public function test_a_range_chart_judges_only_the_upper_side(): void
    {
        $limits = Chart::limits(Chart::RANGE, 0.4);
        $evaluation = Chart::evaluate([['value' => 0.0], ['value' => 1.1], ['value' => 1.4]], $limits);

        $this->assertSame(Chart::IN_CONTROL, $evaluation[0]['state']);
        $this->assertSame(Chart::WARNING, $evaluation[1]['state']);
        $this->assertSame(['action', 'two_of_three'], $evaluation[2]['rules']);
    }

    public function test_limits_are_computed_from_the_mean_and_sample_standard_deviation(): void
    {
        $computed = Chart::computeLimits(Chart::MEAN, [9, 10, 11, 9, 10, 11, 9, 10, 11, 10]);

        $this->assertSame(10.0, $computed['centre_line']);
        $this->assertEqualsWithDelta(sqrt(6 / 9), $computed['standard_deviation'], 1e-12);
        $this->assertSame(10, $computed['point_count']);

        $range = Chart::computeLimits(Chart::RANGE, array_fill(0, 10, 0.3));
        $this->assertEqualsWithDelta(0.3, $range['centre_line'], 1e-12);
        $this->assertNull($range['standard_deviation']);
    }

    public function test_limits_need_enough_points_with_some_spread(): void
    {
        try {
            Chart::computeLimits(Chart::MEAN, array_fill(0, 9, 10.0));
            $this->fail('Nine points must not be enough.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('10 pontos', $exception->getMessage());
        }

        $this->expectExceptionMessage('dispersão');
        Chart::computeLimits(Chart::MEAN, array_fill(0, 12, 10.0));
    }
}
