<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotificationRequest extends FormRequest
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
                'title' => 'required|min:2',
                'message' => 'required|min:10',
                'user_id' => 'nullable|exists:users,id',
            ];
        } else {
            $rules = [
                'title' => 'required|min:2',
                'message' => 'required|min:10',
                'user_id' => 'nullable|exists:users,id',
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
            'title' => trans('gestlab.general.labels.notifications.title'),
            'message' => trans('gestlab.general.labels.notifications.message'),
            'user_id' => trans('gestlab.general.labels.notifications.user_id'),
            'sender' => trans('gestlab.general.labels.notifications.sender'),
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
            'user_id' => !is_null(request()->user_id) ? request()->user_id['value'] : null,
        ]);
    }
}
