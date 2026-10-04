<?php

namespace App\Http\Requests;

use App\Models\InventoryItemSupplier;
use App\Models\InventoryNeed;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConvertInventoryNeedToOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(SampleLaboratoryAccess $access): bool
    {
        $need = $this->route('need');
        abort_unless($need instanceof InventoryNeed && (int) $need->lab_id === $access->activeLabId(), 404);
        abort_if($this->session()->has('impersonate'), 403);

        return $this->user()?->can('add_iorders') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', Rule::exists((new InventoryItemSupplier)->getTable(), 'id')->whereNull('deleted_at')],
            'date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'obs' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
