<?php

namespace App\Http\Requests;

use App\Models\VAPLab;
use App\Services\LabNetworkAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLabBrandingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $lab = $this->route('lab');

        return $this->user() !== null && $lab instanceof VAPLab
            && app(LabNetworkAccess::class)->canManageBranding($this->user(), $lab);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'primary_color' => ['present', 'nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }
}
