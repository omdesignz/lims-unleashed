<?php

namespace App\Http\Requests;

use App\Models\InventoryNeed;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RejectInventoryNeedRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(SampleLaboratoryAccess $access): bool
    {
        $need = $this->route('need');
        abort_unless($need instanceof InventoryNeed && (int) $need->lab_id === $access->activeLabId(), 404);
        abort_if($this->session()->has('impersonate'), 403);

        return $this->user()?->can('edit_iorders') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'approval_notes' => ['required', 'string', 'max:5000'],
        ];
    }
}
