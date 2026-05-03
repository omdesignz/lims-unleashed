<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemCategoryRequest extends FormRequest
{
     /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            $rules = [
                'name' => 'required|min:1|unique:item_categories,name',
                'description' => 'nullable',
                'code' => 'required|min:1',
                'parent_id' => 'nullable|integer',
            ];
        } else {
            $rules = [
                'name' => 'required|min:1|unique:item_categories,name,' . request()->parent,
                'description' => 'nullable',
                'code' => 'required|min:1',
                'parent_id' => 'nullable|integer',
            ];
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => trans('gestlab.general.labels.item_categories.name'),
            'code' => trans('gestlab.general.labels.item_categories.code'),
            'description' => trans('gestlab.general.labels.item_categories.description'),
            'parent_id' => trans('gestlab.general.labels.item_categories.parent_id'),
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function prepareForValidation()
    {
        $this->merge([
            'parent_id' => !is_null(request()->parent_id) ? request()->parent_id['value'] : null
        ]);
    }
}
