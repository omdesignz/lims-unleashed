<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetInventoryWarehousesArchivedRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can($this->routeIs('*.restore') ? 'restore_iwarehouses' : 'delete_iwarehouses');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'recordIds' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'recordIds.*' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->route('warehouse') !== null) {
            $this->merge(['recordIds' => [$this->route('warehouse')]]);
        }
    }
}
