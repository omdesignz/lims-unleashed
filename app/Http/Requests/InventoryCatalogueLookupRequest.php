<?php

namespace App\Http\Requests;

use App\Enums\InventoryCategoryType;
use App\Services\InventoryCatalogueAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryCatalogueLookupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return app(InventoryCatalogueAccess::class)->any($this->user(), 'view');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'inventory_type' => ['nullable', Rule::enum(InventoryCategoryType::class)],
        ];
    }
}
