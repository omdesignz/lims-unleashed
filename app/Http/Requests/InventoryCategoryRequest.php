<?php

namespace App\Http\Requests;

use App\Models\ItemCategory;
use App\Services\InventoryCategoryValidation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InventoryCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return ! $this->session()->has('impersonate') && (bool) $this->user()?->can(
            $this->isMethod('post') ? 'add_item_categories' : 'edit_item_categories');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(InventoryCategoryValidation $validation): array
    {
        $category = null;
        if (! $this->isMethod('post')) {
            $id = $this->route('parent') ?? $this->route('category');
            $row = ItemCategory::query()->whereKey($id)->toBase()->first();
            abort_unless($row, 404);
            $category = new ItemCategory;
            $category->setRawAttributes((array) $row, true);
            $category->exists = true;
        }

        return $validation->rules($category);
    }

    protected function prepareForValidation(): void
    {
        $parent = $this->input('parent_id');
        if (is_array($parent) && array_key_exists('value', $parent)) {
            $this->merge(['parent_id' => $parent['value']]);
        }
    }
}
