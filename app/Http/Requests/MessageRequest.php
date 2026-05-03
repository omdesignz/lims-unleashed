<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MessageRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            $rules = [
                'receiver_id' => 'required|exists:users,id',
                'message' => 'required|string',
                'attachments.*' => 'file|mimes:jpg,png,pdf,docx,mp3,wav|max:2048',
            ];
        } else {
            $rules = [
                'message' => 'required|string',
                'edited_at' => 'required',
                'attachments.*' => 'file|mimes:jpg,png,pdf,docx,mp3,wav|max:2048',

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
            'receiver_id' => trans('gestlab.general.labels.messages.receiver_id'),
            'message' => trans('gestlab.general.labels.message.message'),
            'attachments' => trans('gestlab.general.labels.message.attachments'),
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
        if ($this->isMethod('post')) {
            $receiverId = request()->receiver_id;

            if (is_array($receiverId)) {
                $receiverId = $receiverId['value'] ?? null;
            }

            $this->merge([
                'sender_id' => auth()->id(),
                'receiver_id' => $receiverId,
            ]);
        } else {
            $this->merge([
                'edited_at' => now(),
            ]);
        }
    }
}
