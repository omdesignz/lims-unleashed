<?php

namespace App\Http\Requests;

use App\Models\InventoryNeed;
use App\Models\InventoryNeedItem;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveInventoryNeedRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(SampleLaboratoryAccess $access): bool
    {
        $need = $this->route('need');
        abort_unless($need instanceof InventoryNeed && (int) $need->lab_id === $access->activeLabId(), 404);
        abort_if($this->session()->has('impersonate'), 403);

        return $this->user()?->can('edit_iorders') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'approval_notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct', Rule::exists((new InventoryNeedItem)->getTable(), 'id')->where('inventory_need_id', $this->route('need')->id)],
            'items.*.quantity_approved' => ['required', 'numeric', 'decimal:0,4', 'min:0.0001', 'max:99999999999999.9999'],
        ];
    }
}
