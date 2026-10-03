<?php

namespace App\Support;

use App\Models\Matrix;
use App\Models\Parameter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as LaravelValidator;

class ProposalAuthoringPayload
{
    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function prepare(array $data): array
    {
        if (is_array($data['items'] ?? null)) {
            foreach ($data['items'] as &$item) {
                if (is_array($item) && is_string($item['itemable_type'] ?? null)) {
                    $item['itemable_type'] = str_replace('\\\\', '\\', $item['itemable_type']);
                }
            }
            unset($item);
        }

        return $data;
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function rules(bool $creating, array $data): array
    {
        $rules = [
            'service_location' => ['required', 'string', 'max:255'],
            'obs' => ['nullable', 'string'],
            'tolerance_days' => ['required', 'integer', 'min:1', 'max:365'],
            'withhold_tax' => ['boolean'],
            'use_matrix_price' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['nullable', 'integer', 'min:1'],
            'items.*.item_description' => ['required', 'string', 'max:255'],
            'items.*.standard_id' => ['nullable', 'integer', Rule::exists('standards', 'id')->whereNull('deleted_at')],
            'items.*.unit_id' => ['required', 'integer', Rule::exists('units', 'id')->whereNull('deleted_at')],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.itemable_type' => ['nullable', 'required_with:items.*.itemable_id', Rule::in([Matrix::class, Parameter::class])],
            'items.*.itemable_id' => ['nullable', 'required_with:items.*.itemable_type', 'integer', 'min:1'],
            'items.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_id' => ['nullable', 'integer', Rule::exists('discount_categories', 'id')->whereNull('deleted_at')],
            'items.*.tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_id' => ['nullable', 'integer', Rule::exists('tax_types', 'id')->whereNull('deleted_at')],
            'items.*.charge_tax' => ['boolean'],
            'items.*.withhold_tax' => ['boolean'],
            'items.*.exemption_id' => ['nullable', 'integer', Rule::exists('tax_exemptions', 'id')->whereNull('deleted_at')],
            'items.*.exemption_code' => ['nullable', 'string', 'max:50'],
            'items.*.obs' => ['nullable', 'string'],
        ];
        if ($creating) {
            $rules += [
                'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
                'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('customer_id', filter_var($data['customer_id'] ?? null, FILTER_VALIDATE_INT) ?: null)->whereNull('deleted_at')],
                'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->whereNull('deleted_at')],
                'template_id' => ['required', 'integer', Rule::exists('proposal_templates', 'id')->whereNull('deleted_at')],
            ];
        } else {
            $rules['revision_reason'] = ['required', 'string', 'min:10'];
        }

        return $rules;
    }

    public static function checkSources(LaravelValidator $validator): void
    {
        foreach ((array) data_get($validator->getData(), 'items', []) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }
            $type = $item['itemable_type'] ?? null;
            $id = $item['itemable_id'] ?? null;
            if (in_array($type, [Matrix::class, Parameter::class], true)
                && filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
                && ! $type::query()->whereKey($id)->exists()) {
                $validator->errors()->add("items.{$index}.itemable_id", 'A origem do item já não está disponível.');
            }
            if (is_string($type) && filled($type) && is_scalar($item['item_id'] ?? null) && is_scalar($id)
                && (string) $item['item_id'] !== (string) $id) {
                $validator->errors()->add("items.{$index}.item_id", 'A referência do item não corresponde à origem seleccionada.');
            }
        }
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function validate(array $data, bool $creating): array
    {
        $data = self::prepare($data);
        $validator = Validator::make($data, self::rules($creating, $data));
        $validator->after(self::checkSources(...));

        return $validator->validate();
    }

    /** @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public function normalizeItems(array $items): array
    {
        return collect($items)
            ->map(function (array $item): array {
                $quantity = (float) $item['qty'];
                $unitPrice = (float) $item['unit_price'];
                $grossTotal = $quantity * $unitPrice;
                $discountPercent = (float) ($item['discount_percentage'] ?? 0);
                $discountAmount = isset($item['discount_amount']) && (float) $item['discount_amount'] > 0
                    ? min((float) $item['discount_amount'], $grossTotal)
                    : $grossTotal * ($discountPercent / 100);
                $netTotal = max($grossTotal - $discountAmount, 0);
                $taxPercent = (float) ($item['tax_percentage'] ?? 0);
                $taxAmount = 0.0;

                if ($item['charge_tax'] ?? false) {
                    $taxAmount = isset($item['tax_amount']) && (float) $item['tax_amount'] > 0
                        ? (float) $item['tax_amount']
                        : ($netTotal * ($taxPercent / 100));
                }

                return [
                    'item_id' => $item['item_id'] ?? null,
                    'itemable_type' => $item['itemable_type'] ?? null,
                    'itemable_id' => $item['itemable_id'] ?? null,
                    'item_description' => $item['item_description'],
                    'standard_id' => $item['standard_id'] ?? null,
                    'unit_id' => $item['unit_id'],
                    'qty' => round($quantity, 2),
                    'unit_price' => round($unitPrice, 2),
                    'total' => round($netTotal, 2),
                    'discount_percentage' => round($discountPercent, 2),
                    'discount_amount' => round($discountAmount, 2),
                    'discount_id' => $item['discount_id'] ?? null,
                    'tax_percentage' => round($taxPercent, 2),
                    'tax_amount' => round($taxAmount, 2),
                    'tax_id' => $item['tax_id'] ?? null,
                    'charge_tax' => (bool) ($item['charge_tax'] ?? false),
                    'withhold_tax' => (bool) ($item['withhold_tax'] ?? false),
                    'exemption_id' => $item['exemption_id'] ?? null,
                    'exemption_code' => $item['exemption_code'] ?? null,
                    'obs' => $item['obs'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{sub_total: float, discount: float, tax: float, total: float}
     */
    public function totals(array $items): array
    {
        $subTotal = collect($items)->sum(fn (array $item): float => (float) $item['total']);
        $discount = collect($items)->sum(fn (array $item): float => (float) $item['discount_amount']);
        $tax = collect($items)->sum(fn (array $item): float => (float) $item['tax_amount']);

        return [
            'sub_total' => round($subTotal, 2),
            'discount' => round($discount, 2),
            'tax' => round($tax, 2),
            'total' => round($subTotal + $tax, 2),
        ];
    }
}
