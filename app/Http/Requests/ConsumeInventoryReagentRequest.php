<?php

namespace App\Http\Requests;

use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConsumeInventoryReagentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(SampleLaboratoryAccess $access): bool
    {
        abort_unless($this->user()?->can('add_reagent_consumption') && ! $this->session()->has('impersonate'), 403);
        $item = $this->route('item');
        if ($item instanceof InventoryItem) {
            abort_unless((int) $item->lab_id === $access->activeLabId(), 404);
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(SampleLaboratoryAccess $access): array
    {
        $labId = $access->activeLabId();
        $registerForm = $this->routeIs('vap-inventory.reagents.consumption.store');

        return [
            'reagent_id' => [$registerForm ? 'required' : 'nullable', 'integer',
                Rule::exists((new InventoryItem)->getTable(), 'id')->where('lab_id', $labId)->whereNull('deleted_at')],
            'warehouse_id' => ['required', 'integer',
                Rule::exists((new InventoryItemWarehouse)->getTable(), 'id')->where('lab_id', $labId)->whereNull('deleted_at')],
            'quantity_used' => ['required', 'numeric', 'decimal:0,4', 'min:0.0001'],
            'used_by' => ['required', 'string', 'max:255'], 'remarks' => ['nullable', 'string', 'max:1000'],
            'batch_id' => ['nullable', 'integer', 'min:1'], 'usage_type' => ['nullable', 'string', 'max:255'],
            'project' => ['nullable', 'string', 'max:255'], 'date' => [$registerForm ? 'required' : 'nullable', 'date'],
            'used_at' => ['nullable', 'date'],
        ];
    }
}
