<?php

namespace App\Http\Requests;

use App\Services\InventoryCatalogueAccess;
use App\Services\InventoryItemCreationValidation;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateInventoryItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return ! $this->session()->has('impersonate') && app(InventoryCatalogueAccess::class)->any($this->user(), 'add');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(InventoryItemCreationValidation $validation, SampleLaboratoryAccess $access): array
    {
        return $validation->rules($access->activeLabId());
    }
}
