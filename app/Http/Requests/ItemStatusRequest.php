<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemStatusRequest extends FormRequest
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
                'name' => 'required|min:1|unique:item_statuses,name',
                'description' => 'nullable',
                'category_id' => 'nullable|exists:item_categories,id'
            ];
        } else {
            $rules = [
                'name' => 'required|min:1|unique:item_statuses,name,' . request()->status,
                'description' => 'nullable',
                'category_id' => 'nullable|exists:item_categories,id'

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
            'name' => trans('gestlab.general.labels.item_statuses.name'),
            'description' => trans('gestlab.general.labels.item_statuses.description'),
            'category_id' => trans('gestlab.general.labels.item_statuses.category_id'),
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
            'category_id' => !is_null(request()->category_id) ? request()->category_id['value'] : null,
        ]);   
    }
}