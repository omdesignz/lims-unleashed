<?php

namespace App\Http\Requests;

use App\Enums\InventoryCategoryType;
use App\Services\InventoryCatalogueAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryCatalogueExportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return app(InventoryCatalogueAccess::class)->any($this->user(), 'export');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'inventory_type' => ['nullable', Rule::enum(InventoryCategoryType::class)],
        ];
    }
}
