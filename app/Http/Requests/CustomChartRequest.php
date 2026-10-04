<?php

namespace App\Http\Requests;

use App\Metrics\ChartDatasets;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * A chart definition from the chart builder. The registry decides whether the
 * person may chart the dataset and whether the measure, grouping, breakdown and
 * form fit together; a title is required only when the chart is saved.
 */
class CustomChartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'title' => [$this->isPreview() ? 'nullable' : 'required', 'string', 'max:120'],
            'dataset' => ['required', 'string', 'max:40'],
            'measure' => ['required', 'string', 'max:40'],
            'dimension' => ['required', 'string', 'max:40'],
            'split' => ['nullable', 'string', 'max:40'],
            'kind' => ['required', 'string', 'max:12'],
            'period' => ['required', 'string', 'max:12'],
            'colors' => ['nullable', 'array', 'max:24'],
            'colors.*' => ['string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'title.required' => 'Dê um título ao gráfico.',
            'colors.*.regex' => 'Cada cor tem de ser um código hexadecimal, por exemplo #087cf0.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['dataset', 'measure', 'dimension', 'split', 'kind', 'period'])) {
                    return;
                }

                foreach (app(ChartDatasets::class)->problems($this->user(), $this->definition()) as $field => $message) {
                    $validator->errors()->add($field, $message);
                }

                foreach (array_keys((array) $this->input('colors', [])) as $name) {
                    if (! is_string($name) || mb_strlen($name) > 120) {
                        $validator->errors()->add('colors', 'As cores têm de ser atribuídas a séries com nome.');
                    }
                }
            },
        ];
    }

    /**
     * @return array{dataset: string, measure: string, dimension: string, split: string|null, kind: string, period: string}
     */
    public function definition(): array
    {
        return [
            'dataset' => (string) $this->input('dataset'),
            'measure' => (string) $this->input('measure'),
            'dimension' => (string) $this->input('dimension'),
            'split' => filled($this->input('split')) ? (string) $this->input('split') : null,
            'kind' => (string) $this->input('kind'),
            'period' => (string) $this->input('period'),
        ];
    }

    private function isPreview(): bool
    {
        return $this->routeIs('analytics.charts.preview');
    }
}
