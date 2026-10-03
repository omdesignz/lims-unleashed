<?php

namespace App\Http\Requests;

use App\Models\IntegrationConnector;
use App\Services\IntegrationConnectorValidation;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreIntegrationConnectorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(SampleLaboratoryAccess $laboratory): bool
    {
        if (! $this->user()?->can('edit_iequipments') && ! $this->user()?->can('edit_settings')) {
            return false;
        }
        $labId = $laboratory->activeLabId();
        $connector = $this->route('connector');
        if ($connector instanceof IntegrationConnector) {
            abort_unless((int) $connector->lab_id === $labId, 404);
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(SampleLaboratoryAccess $laboratory, IntegrationConnectorValidation $validation): array
    {
        return $validation->rules($laboratory->activeLabId(), $this->only(['inventory_item_id', 'direction']), $this->route('connector'));
    }
}
