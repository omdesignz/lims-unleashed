<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FAQRequest extends FormRequest
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
                'description' => 'required|unique:faqs',
                'category_id' => 'required|exists:faq_categories,id',
            ];
        } else {
            $rules = [
                'description' => 'required|min:1|unique:faqs,description,' . request()->faq,
                'category_id' => 'required|exists:faq_categories,id',

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
            'description' => trans('gestlab.general.labels.faqs.description'),
            'category_id' => trans('gestlab.general.labels.faqs.category_id'),
        ];
    }

    /**
 * Get the error messages for the defined validation rules.
 *
 * @return array<string, string>
 */
public function messages(): array
{
    return [];   
}

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function prepareForValidation()
    {
        // dd(request()->all());
        $this->merge([
            'category_id' => !is_null(request()->category_id) ? request()->category_id['value'] : null,
        ]);
    }
}
