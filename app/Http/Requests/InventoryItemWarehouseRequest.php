<?php

namespace App\Http\Requests;

use App\Models\InventoryItemWarehouse;
use App\Services\InventoryWarehouseValidation;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Foundation\Http\FormRequest;

class InventoryItemWarehouseRequest extends FormRequest
{
    private ?InventoryItemWarehouse $warehouse = null;

    public function authorize(): bool
    {
        $permission = $this->isMethod('post') ? 'add_iwarehouses' : 'edit_iwarehouses';
        abort_unless(! $this->session()->has('impersonate') && $this->user()?->can($permission), 403);
        $labId = app(SampleLaboratoryAccess::class)->activeLabId();

        if (! $this->isMethod('post')) {
            $row = InventoryItemWarehouse::query()->where('lab_id', $labId)->toBase()->find($this->warehouseId());
            abort_unless($row, 404);
            $this->warehouse = new InventoryItemWarehouse;
            $this->warehouse->setRawAttributes((array) $row, true);
            $this->warehouse->exists = true;
        }

        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $labId = app(SampleLaboratoryAccess::class)->activeLabId();

        return app(InventoryWarehouseValidation::class)->rules($labId, $this->warehouse);
    }

    private function warehouseId(): int
    {
        return (int) ($this->route('iwarehouse') ?? $this->route('warehouse'));
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => trans('gestlab.general.labels.iwarehouses.name'),
            'is_refrigerated' => trans('gestlab.general.labels.iwarehouses.is_refrigerated'),
            'is_ventilated' => trans('gestlab.general.labels.iwarehouses.is_ventilated'),
            'has_air_exhaustion' => trans('gestlab.general.labels.iwarehouses.has_air_exhaustion'),
            'location_id' => trans('gestlab.general.labels.iwarehouses.location_id'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $location = $this->input('location_id');
        $this->merge([
            'location_id' => is_array($location) ? ($location['value'] ?? null) : $location,
        ]);
    }
}
