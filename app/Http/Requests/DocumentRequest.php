<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentRequest extends FormRequest
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
                'title' => 'required|string',
                'description' => 'nullable|string',
                'version' => 'nullable|integer',
                'file' => 'nullable|file',
            ];
        } else {
            $rules = [
                'title' => 'required|min:3|unique:documents,title,' . request()->document,
                'description' => 'nullable|string',
                'version' => 'nullable|integer',
                'file' => 'nullable|file',
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
            'title' => trans('gestlab.general.labels.documents.title'),
            'description' => trans('gestlab.general.labels.documents.description'),
            'file' => trans('gestlab.general.labels.documents.file'),
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
