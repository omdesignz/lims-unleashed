<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CurrencyRequest extends FormRequest
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
    public function rules()
    {
        if ($this->isMethod('post')) {
            $rules = [
                'name' => 'required|min:3',
                'code' => 'required|min:1',
                'symbol' => 'required|min:1',
                'thousand_separator' => 'required',
                'decimal_separator' => 'required',
                'swap_currency_symbol' => 'required|boolean',
            ];
        } else {
            $rules = [
                'name' => 'required|min:3|unique:currencies,name,' . request()->currency,
                'code' => 'required|min:1',
                'symbol' => 'required|min:1',
                'thousand_separator' => 'required',
                'decimal_separator' => 'required',
                'swap_currency_symbol' => 'required|boolean',
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
            'name' => trans('gestlab.general.labels.currencies.name'),
            'code' => trans('gestlab.general.labels.currencies.code'),
            'symbol' => trans('gestlab.general.labels.currencies.symbol'),
            'thousand_separator' => trans('gestlab.general.labels.currencies.thousand_separator'),
            'decimal_separator' => trans('gestlab.general.labels.currencies.decimal_separator'),
            'swap_currency_symbol' => trans('gestlab.general.labels.currencies.swap_currency_symbol'),
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
        // if ($this->isMethod('post')) {
        //     $this->merge([
        //         'password' => bcrypt('password'),
        //     ]);
        // }
    }
}
