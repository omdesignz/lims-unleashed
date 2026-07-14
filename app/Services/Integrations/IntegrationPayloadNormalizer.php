<?php

namespace App\Services\Integrations;

use App\Models\IntegrationMapping;
use App\Models\Parameter;
use App\Models\Result;
use App\Models\Sample;
use Carbon\CarbonImmutable;
use Throwable;

class IntegrationPayloadNormalizer
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalize(array $payload, ?IntegrationMapping $mapping): array
    {
        if (! $mapping) {
            return [
                'values' => [],
                'matches' => [],
                'issues' => ['Nenhum mapeamento activo está configurado para este conector.'],
            ];
        }

        $values = [];

        foreach (IntegrationMapping::SUPPORTED_FIELDS as $field) {
            $constant = data_get($mapping->constants, $field);
            $path = data_get($mapping->field_paths, $field);
            $value = filled($constant) ? $constant : (filled($path) ? data_get($payload, $path) : null);

            foreach ((array) data_get($mapping->transformations, $field, []) as $transformation) {
                $value = $this->transform($value, $transformation);
            }

            $values[$field] = is_scalar($value) || $value === null ? $value : json_encode($value);
        }

        $sample = filled($values['sample_code'])
            ? Sample::query()->where('code', (string) $values['sample_code'])->first()
            : null;
        $parameter = filled($values['parameter_code'])
            ? Parameter::query()->where('code', (string) $values['parameter_code'])->first()
            : null;
        $result = $sample && $parameter
            ? Result::query()->where('sample_id', $sample->id)->where('parameter_id', $parameter->id)->first()
            : null;

        $issues = [];

        if (! filled($values['sample_code'])) {
            $issues[] = 'O mapeamento não produziu um código de amostra.';
        } elseif (! $sample) {
            $issues[] = 'O código de amostra não corresponde a uma amostra registada.';
        }

        if (! filled($values['parameter_code'])) {
            $issues[] = 'O mapeamento não produziu um código de parâmetro.';
        } elseif (! $parameter) {
            $issues[] = 'O código de parâmetro não corresponde a um parâmetro registado.';
        }

        if ($sample && $parameter && ! $result) {
            $issues[] = 'A amostra não possui um resultado preparado para este parâmetro.';
        }

        if (! filled($values['value']) && $values['value'] !== 0 && $values['value'] !== '0') {
            $issues[] = 'O mapeamento não produziu um valor de resultado.';
        }

        return [
            'values' => $values,
            'matches' => [
                'sample_id' => $sample?->id,
                'sample_code' => $sample?->code,
                'parameter_id' => $parameter?->id,
                'parameter_name' => $parameter?->name,
                'result_id' => $result?->id,
            ],
            'issues' => $issues,
        ];
    }

    public function parseMeasuredAt(mixed $value): ?CarbonImmutable
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value);
        } catch (Throwable) {
            return null;
        }
    }

    private function transform(mixed $value, string $transformation): mixed
    {
        if (! is_scalar($value)) {
            return $value;
        }

        $text = (string) $value;

        return match ($transformation) {
            'trim' => trim($text),
            'uppercase' => mb_strtoupper($text),
            'lowercase' => mb_strtolower($text),
            'decimal_comma' => str_replace(',', '.', preg_replace('/\s+/', '', $text) ?? $text),
            default => $value,
        };
    }
}
