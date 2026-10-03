<?php

namespace App\Http\Requests;

use App\Services\SampleLaboratoryAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportOccurrencesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        app(SampleLaboratoryAccess::class)->activeLabId();

        return $this->user()->can('add_occurrences');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'extensions:csv,txt', 'max:2048'],
        ];
    }
}
