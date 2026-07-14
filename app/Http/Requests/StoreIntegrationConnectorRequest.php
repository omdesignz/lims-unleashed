<?php

namespace App\Http\Requests;

use App\Models\IntegrationConnector;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIntegrationConnectorRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $connector = $this->route('connector');

        return [
            'inventory_item_id' => ['nullable', 'integer', 'exists:i_items,id'],
            'name' => ['required', 'string', 'max:120'],
            'key' => [
                'nullable',
                'string',
                'max:80',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('integration_connectors', 'key')->ignore($connector?->id),
            ],
            'direction' => ['required', Rule::in(IntegrationConnector::DIRECTIONS)],
            'adapter' => ['required', Rule::in(IntegrationConnector::ADAPTERS)],
            'status' => ['required', Rule::in(IntegrationConnector::STATUSES)],
            'description' => ['nullable', 'string', 'max:2000'],
            'configuration' => ['nullable', 'array'],
            'configuration.endpoint' => [
                Rule::requiredIf(fn (): bool => in_array($this->string('direction')->value(), ['outbound', 'bidirectional'], true)),
                'nullable',
                'url:http,https',
                'max:2000',
            ],
            'configuration.timeout_seconds' => ['nullable', 'integer', 'between:2,30'],
            'configuration.edge_agent_id' => ['nullable', 'string', 'max:120'],
            'credentials' => ['nullable', 'array'],
            'credentials.bearer_token' => ['nullable', 'string', 'max:2000'],
            'event_types' => ['nullable', 'array'],
            'event_types.*' => ['string', Rule::in(['lims.analysis.validated', 'lims.connector.test'])],
        ];
    }
}
