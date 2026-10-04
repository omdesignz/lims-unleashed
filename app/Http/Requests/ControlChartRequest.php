<?php

namespace App\Http\Requests;

use App\Models\ControlChart;
use App\Support\ControlChartEvaluation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ControlChartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'name' => [$creating ? 'required' : 'sometimes', 'required', 'string', 'max:160'],
            // The type decides what a point is; it is fixed once the chart exists.
            'chart_type' => $creating ? ['required', Rule::in(ControlChartEvaluation::TYPES)] : ['prohibited'],
            'parameter_id' => ['nullable', 'integer', 'exists:parameters,id'],
            'method' => ['nullable', 'string', 'max:160'],
            'matrix' => ['nullable', 'string', 'max:160'],
            'control_material' => ['nullable', 'string', 'max:160'],
            'material_lot' => ['nullable', 'string', 'max:80'],
            'unit' => ['nullable', 'string', 'max:40'],
            'centre_line' => ['nullable', 'numeric', 'required_with:standard_deviation', 'between:-1000000000,1000000000'],
            'standard_deviation' => ['nullable', 'numeric', 'gt:0', 'max:1000000000'],
            'limits_basis' => ['nullable', 'string', 'max:2000', 'required_with:centre_line'],
            'status' => ['sometimes', Rule::in(array_keys(ControlChart::STATUSES))],
            'notes' => ['nullable', 'string', 'max:4000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $type = $this->isMethod('post') ? $this->input('chart_type') : $this->route('chart')?->chart_type;

            if ($type === ControlChartEvaluation::MEAN && filled($this->input('centre_line')) && blank($this->input('standard_deviation'))) {
                $validator->errors()->add('standard_deviation', 'Uma carta de médias precisa do desvio-padrão para traçar os limites.');
            }

            if ($type === ControlChartEvaluation::RANGE && filled($this->input('centre_line')) && (float) $this->input('centre_line') <= 0) {
                $validator->errors()->add('centre_line', 'A amplitude média tem de ser positiva.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $parameter = $this->input('parameter_id');
        $decimal = fn (mixed $value): mixed => is_string($value) ? (trim($value) === '' ? null : str_replace(',', '.', trim($value))) : $value;

        // Only what was sent: a partial update (e.g. archiving) must not clear the limits.
        $this->merge(collect([
            'parameter_id' => is_array($parameter) ? ($parameter['value'] ?? null) : $parameter,
            'centre_line' => $decimal($this->input('centre_line')),
            'standard_deviation' => $decimal($this->input('standard_deviation')),
        ])->filter(fn (mixed $value, string $key): bool => $this->has($key))->all());
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'chart_type' => 'tipo de carta',
            'parameter_id' => 'parâmetro',
            'method' => 'método',
            'matrix' => 'matriz',
            'control_material' => 'material de controlo',
            'material_lot' => 'lote do material',
            'unit' => 'unidade',
            'centre_line' => 'linha central',
            'standard_deviation' => 'desvio-padrão',
            'limits_basis' => 'fundamento dos limites',
            'status' => 'estado',
            'notes' => 'notas',
        ];
    }
}
