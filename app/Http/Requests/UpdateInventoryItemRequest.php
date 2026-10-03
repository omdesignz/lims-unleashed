<?php

namespace App\Http\Requests;

use App\Models\InventoryItem;
use App\Services\InventoryCatalogueAccess;
use App\Services\InventoryItemUpdateValidation;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return ! $this->session()->has('impersonate') && app(InventoryCatalogueAccess::class)->any($this->user(), 'edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(InventoryItemUpdateValidation $validation, SampleLaboratoryAccess $access): array
    {
        $item = $this->route('item');
        abort_unless($item instanceof InventoryItem && (int) $item->lab_id === $access->activeLabId(), 404);

        return $validation->rules($access->activeLabId(), $item);
    }

    /** @return array<string,string> */
    public function messages(): array
    {
        return ['category_id.in' => 'A categoria integra a sequência emitida e não pode ser alterada.',
            'unit_id.in' => 'A unidade não pode ser alterada depois de criar existências para o item.'];
    }
}
