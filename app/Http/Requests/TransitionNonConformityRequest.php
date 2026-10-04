<?php

namespace App\Http\Requests;

use App\Services\SampleLaboratoryAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionNonConformityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        abort_unless((int) $this->route('nonConformity')->lab_id === app(SampleLaboratoryAccess::class)->activeLabId(), 404);

        return ! $this->session()->has('impersonate')
            && $this->user()->can($this->route('transition').'_non_conformities');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return self::transitionRules($this->route('transition'));
    }

    /** @return array<string, array<int, mixed>> */
    public static function transitionRules(string $transition): array
    {
        return [
            'request_id' => ['required', 'uuid'],
            'workflow_revision' => ['required', 'integer', 'min:0'],
            'evidence' => [Rule::requiredIf(in_array($transition, ['resolve', 'verify', 'reopen'], true)), 'nullable', 'string', 'max:20000', 'regex:/\S/u'],
        ];
    }
}
