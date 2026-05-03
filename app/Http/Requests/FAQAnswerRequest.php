<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FAQAnswerRequest extends FormRequest
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
                'description' => 'required',
                'faq_id' => 'required|exists:faqs,id',
            ];
        } else {
            $rules = [
                'description' => 'required',
                'faq_id' => 'required|exists:faqs,id',

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
            'description' => trans('gestlab.general.labels.faq_answers.description'),
            'faq_id' => trans('gestlab.general.labels.faq_answers.faq_id'),
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
            'faq_id' => !is_null(request()->faq_id) ? request()->faq_id['value'] : null,
        ]);
    }
}
