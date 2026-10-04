<?php

namespace App\Http\Requests;

use App\Services\LabNetworkAccess;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LabNetworkStockRequest extends FormRequest
{
    public function authorize(LabNetworkAccess $access): bool
    {
        return $this->user() !== null && $access->visibleLabs($this->user(), $this->route('network'))->exists();
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(LabNetworkAccess $access): array
    {
        $labIds = $access->visibleLabs($this->user(), $this->route('network'))->pluck('labs.id')->all();
        $warehouses = DB::table('i_warehouses')->whereIn('lab_id', $labIds)->whereNull('deleted_at')
            ->when(filter_var($this->input('lab_id'), FILTER_VALIDATE_INT), fn (Builder $query, int $id): Builder => $query->where('lab_id', $id))->pluck('id')->all();

        return [
            'search' => ['nullable', 'string', 'max:100'],
            'lot' => ['nullable', 'string', 'max:100'],
            'lab_id' => ['nullable', 'integer', Rule::in($labIds)],
            'warehouse_id' => ['nullable', 'integer', Rule::in($warehouses)],
            'expiry_from' => ['nullable', 'date_format:Y-m-d'],
            'expiry_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:expiry_from'],
            'available' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', Rule::in([25, 50, 100])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
