<?php

namespace App\Http\Requests;

use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustInventoryItemStockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(SampleLaboratoryAccess $access): bool
    {
        abort_unless($this->user()?->can('edit_inventory'), 403);
        $item = $this->route('item');
        abort_unless($item instanceof InventoryItem && (int) $item->lab_id === $access->activeLabId(), 404);

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

        return [
            'warehouse_id' => ['required', 'integer', Rule::exists((new InventoryItemWarehouse)->getTable(), 'id')->where('lab_id', $labId)->whereNull('deleted_at')],
            'adjustment_type' => ['required', 'in:add,remove,set'],
            'quantity' => ['required', 'numeric', 'decimal:0,4', 'min:'.($this->input('adjustment_type') === 'set' ? '0' : '0.0001')],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'batch_id' => ['nullable', 'integer', Rule::exists((new InventoryBatch)->getTable(), 'id')->where('lab_id', $labId)],
        ];
    }
}
