<?php

namespace App\Services;

use App\Models\IntegrationConnector;
use App\Models\InventoryItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class IntegrationConnectorValidation
{
    /**
     * @param  array<string,mixed>  $data
     * @return array<string,ValidationRule|array<mixed>|string>
     */
    public function rules(int $labId, array $data, ?IntegrationConnector $connector = null): array
    {
        $equipment = InventoryItem::forLaboratory($labId)->equipment()->select('id');
        $selectedId = $data['inventory_item_id'] ?? null;
        if ($connector !== null && $connector->inventory_item_id !== null && is_scalar($selectedId)
            && (string) $selectedId === (string) $connector->inventory_item_id) {
            $equipment->withTrashed();
        }

        return [
            'inventory_item_id' => ['bail', 'nullable', 'integer', Rule::exists(InventoryItem::class, 'id')
                ->where(fn (Builder $query): Builder => $query->whereIn('id', $equipment))],
            'name' => ['required', 'string', 'max:120'],
            'key' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique(IntegrationConnector::class, 'key')->ignore($connector?->id)],
            'direction' => ['required', Rule::in(IntegrationConnector::DIRECTIONS)],
            'adapter' => ['required', Rule::in(IntegrationConnector::ADAPTERS)],
            'status' => ['required', Rule::in(IntegrationConnector::STATUSES)],
            'description' => ['nullable', 'string', 'max:2000'],
            'configuration' => ['nullable', 'array'],
            'configuration.endpoint' => [Rule::requiredIf(in_array($data['direction'] ?? null, ['outbound', 'bidirectional'], true)),
                'nullable', 'url:http,https', 'max:2000'],
            'configuration.timeout_seconds' => ['nullable', 'integer', 'between:2,30'],
            'configuration.edge_agent_id' => ['nullable', 'string', 'max:120'],
            'credentials' => ['nullable', 'array'],
            'credentials.bearer_token' => ['nullable', 'string', 'max:2000'],
            'event_types' => ['nullable', 'array'],
            'event_types.*' => ['string', Rule::in(['lims.analysis.validated', 'lims.connector.test'])],
        ];
    }
}
