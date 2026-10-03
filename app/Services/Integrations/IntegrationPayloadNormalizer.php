<?php

namespace App\Services\Integrations;

use App\Models\Analysis;
use App\Models\IntegrationConnector;
use App\Models\IntegrationMapping;
use App\Models\Parameter;
use App\Services\IssuedAnalyticalScope;
use App\Services\LaboratoryWorkflowOwnership;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Throwable;

class IntegrationPayloadNormalizer
{
    public function __construct(
        private readonly LaboratoryWorkflowOwnership $ownership,
        private readonly IssuedAnalyticalScope $issuedScope,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalize(array $payload, IntegrationConnector $connector, ?IntegrationMapping $mapping): array
    {
        if (! $mapping || (int) $mapping->connector_id !== (int) $connector->id) {
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

        $issues = [];
        $samples = filled($values['sample_code'])
            ? $this->ownership->samplesForLaboratory((int) $connector->lab_id)
                ->where('code', (string) $values['sample_code'])->limit(2)->get()
            : collect();
        $sample = $samples->count() === 1 ? $samples->first() : null;
        $analysis = $sample?->analysis;
        $product = $sample?->collection?->collection;
        $issued = collect();

        if ($analysis && $product) {
            try {
                $issued = $this->issuedScope->parametersFor($analysis, $product);
            } catch (ValidationException) {
                $issues[] = 'O âmbito analítico emitido desta amostra precisa de correcção antes da importação.';
            }
        }

        $parameters = $issued->filter(fn (Parameter $candidate): bool => $candidate->code === $values['parameter_code']);
        $parameter = $parameters->count() === 1 ? $parameters->first() : null;
        $results = $sample && $analysis && $product && $parameter
            ? $this->ownership->resultsForLaboratory((int) $connector->lab_id)
                ->where('sample_id', $sample->id)
                ->where('code_id', $sample->cl_id)
                ->where('collection_id', $product->id)
                ->where('product_id', $product->product_id)
                ->where('profile_id', $analysis->profile_id)
                ->where('parameter_id', $parameter->id)
                ->where('resultable_type', (new Analysis)->getMorphClass())
                ->where('resultable_id', $analysis->id)
                ->limit(2)->get()
            : collect();
        $result = $results->count() === 1 ? $results->first() : null;

        if (! filled($values['sample_code'])) {
            $issues[] = 'O mapeamento não produziu um código de amostra.';
        } elseif (! $sample) {
            $issues[] = 'O código de amostra não corresponde a uma amostra registada.';
        } elseif (! $analysis || ! $product) {
            $issues[] = 'A amostra não possui uma análise emitida completa.';
        }

        if (! filled($values['parameter_code'])) {
            $issues[] = 'O mapeamento não produziu um código de parâmetro.';
        } elseif ($sample && ! $parameter) {
            $issues[] = 'O código de parâmetro não pertence ao âmbito analítico emitido da amostra.';
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
