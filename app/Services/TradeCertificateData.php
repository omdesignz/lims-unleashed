<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as Validation;
use LogicException;

class TradeCertificateData
{
    /** @return list<string> */
    public function lineFields(string $kind): array
    {
        return $kind === 'import' ? ['product_id', 'qty', 'origin', 'validity', 'lot', 'bl_no'] : ['product_id', 'qty'];
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function normalize(string $kind, array $input): array
    {
        foreach (['importer_id', 'importer_warehouse_id', 'exporter_id', 'exporter_warehouse_id', 'currency_id', 'trans_type_id',
            'destination_country_id', 'country_origin_id', 'country_destination_id'] as $field) {
            if (array_key_exists($field, $input)) {
                $input[$field] = $this->option($input[$field]);
            }
        }
        if (is_array($input['items'] ?? null)) {
            $input['items'] = array_map(function (mixed $item) use ($kind): mixed {
                if (! is_array($item)) {
                    return $item;
                }
                $item = Arr::only($item, $this->lineFields($kind));
                if (array_key_exists('product_id', $item)) {
                    $item['product_id'] = $this->option($item['product_id']);
                }

                return $item;
            }, $input['items']);
        }

        return $input;
    }

    private function option(mixed $value): mixed
    {
        return is_array($value) && array_key_exists('value', $value) ? $value['value'] : $value;
    }

    /** @return array<string, mixed> */
    public function rules(string $kind, bool $observationsOnly = false): array
    {
        if (! in_array($kind, ['import', 'export'], true)) {
            throw new LogicException('Unsupported trade certificate kind.');
        }
        $rules = ['obs' => [$observationsOnly ? 'present' : 'nullable', 'nullable', 'string', 'max:5000']];
        foreach (['lab_id', 'user_id', 'cert_no', 'certificate_year', 'seq', 'invoice_id', 'invoiced', 'file_path', 'file', 'unique_hash', 'deleted_at'] as $field) {
            $rules[$field] = ['missing'];
        }
        if ($observationsOnly) {
            return $rules;
        }
        $rules += [
            'authorized_personnel' => ['required', 'string', 'max:255'], 'date' => ['required', 'date_format:Y-m-d'],
            'items' => ['required', 'array', 'list', 'min:1', 'max:100'], 'items.*' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('phytosanitary_products', 'id')->whereNull('deleted_at')],
            'items.*.qty' => ['required', 'numeric', 'gt:0', 'regex:/^\d{1,8}(\.\d{1,2})?$/'],
        ];
        $directories = ['exporter_id' => 'customers', 'exporter_warehouse_id' => 'warehouses', 'trans_type_id' => 'trans_categories'];
        $directories += $kind === 'import'
            ? ['importer_id' => 'customers', 'importer_warehouse_id' => 'warehouses', 'currency_id' => 'currencies', 'destination_country_id' => 'countries']
            : ['country_origin_id' => 'countries', 'country_destination_id' => 'countries'];
        foreach ($directories as $field => $table) {
            $rules[$field] = [$field === 'currency_id' ? 'nullable' : 'required', 'integer', Rule::exists($table, 'id')->whereNull('deleted_at')];
        }
        foreach ($kind === 'import' ? ['port_exit', 'port_entry'] : ['origin_city', 'destination_city', 'expedition_location'] as $field) {
            $rules[$field] = ['required', 'string', 'max:255'];
        }
        if ($kind === 'import') {
            foreach (['vat', 'vat_cost', 'cost_freight', 'cost_insurance', 'cost_final'] as $field) {
                $rules[$field] = ['required', 'numeric', 'min:0', 'regex:/^\d{1,8}(\.\d{1,2})?$/'];
            }
            $rules['vat'][] = 'max:100';
            foreach (['origin', 'lot', 'bl_no'] as $field) {
                $rules['items.*.'.$field] = ['nullable', 'string', 'max:255'];
            }
            $rules['items.*.validity'] = ['nullable', 'date_format:Y-m-d'];
        } else {
            $rules['expedition_date'] = ['required', 'date_format:Y-m-d'];
        }

        return $rules;
    }

    /** @param array<string, mixed> $input */
    public function rejectLockedFields(Validation $validator, array $input): void
    {
        foreach (array_diff(array_keys($input), ['obs', '_method', '_token']) as $field) {
            $validator->errors()->add($field, 'O documento facturado está bloqueado. Apenas as observações podem ser corrigidas.');
        }
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function validate(string $kind, array $input, bool $observationsOnly = false): array
    {
        $input = $this->normalize($kind, $input);
        $validator = Validator::make($input, $this->rules($kind, $observationsOnly));
        if ($observationsOnly) {
            $validator->after(fn (Validation $validator) => $this->rejectLockedFields($validator, $input));
        }

        return $validator->validate();
    }
}
