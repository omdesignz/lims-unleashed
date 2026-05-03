<?php

namespace App\Support;

use App\Models\CollectionProduct;
use App\Models\InventoryItem;
use App\Models\VAPSampleEntry;
use InvalidArgumentException;

class LabelStudioSourceResolver
{
    public function resolve(?string $sourceType, mixed $sourceId): ?array
    {
        if (! $sourceType || ! $sourceId) {
            return null;
        }

        return match ($sourceType) {
            'sample', 'sample_entry' => $this->resolveSample((int) $sourceId),
            'equipment' => $this->resolveInventoryItem((int) $sourceId, false),
            'reagent' => $this->resolveInventoryItem((int) $sourceId, true),
            'collection_product' => $this->resolveCollectionProduct((int) $sourceId),
            default => throw new InvalidArgumentException("Unsupported label source [{$sourceType}]."),
        };
    }

    public function renderContent(string $content, array $payload): string
    {
        $replacements = collect($payload)
            ->mapWithKeys(fn ($value, $key) => ['{' . $key . '}' => (string) ($value ?? '')])
            ->all();

        return strtr($content, $replacements);
    }

    public function supportedPlaceholders(): array
    {
        return [
            '{name}',
            '{code}',
            '{type}',
            '{customer}',
            '{department}',
            '{warehouse}',
            '{lot}',
            '{serial_number}',
            '{expiry_date}',
            '{received_at}',
            '{date}',
        ];
    }

    private function resolveSample(int $sampleEntryId): ?array
    {
        $sample = VAPSampleEntry::query()
            ->with(['customer:id,name', 'department:id,name', 'warehouse:id,name'])
            ->find($sampleEntryId);

        if (! $sample) {
            return null;
        }

        return [
            'source_type' => 'sample_entry',
            'source_id' => $sample->id,
            'name' => $sample->name,
            'code' => $sample->code,
            'type' => $sample->sample_type,
            'customer' => $sample->customer?->name,
            'department' => $sample->department?->name,
            'warehouse' => $sample->warehouse?->name,
            'lot' => data_get($sample->client_submitted_info, 'lot'),
            'expiry_date' => $sample->discard_scheduled_at?->format('Y-m-d'),
            'received_at' => $sample->received_at?->format('Y-m-d H:i'),
            'date' => now()->format('Y-m-d'),
            'qr_content' => $sample->code ?: ('sample-entry:' . $sample->id),
            'barcode_content' => $sample->code ?: ('sample-entry:' . $sample->id),
        ];
    }

    private function resolveInventoryItem(int $itemId, bool $reagentMode): ?array
    {
        $item = InventoryItem::query()
            ->with(['category:id,name', 'supplier:id,name'])
            ->find($itemId);

        if (! $item) {
            return null;
        }

        return [
            'source_type' => $reagentMode ? 'reagent' : 'equipment',
            'source_id' => $item->id,
            'name' => $item->name,
            'code' => $item->code ?: $item->internal_code,
            'type' => $reagentMode ? 'Reagente' : 'Equipamento',
            'customer' => null,
            'department' => null,
            'warehouse' => $item->location,
            'lot' => $item->lot,
            'serial_number' => $item->serial_number,
            'expiry_date' => optional($item->reagent_expiry_date)->format('Y-m-d'),
            'received_at' => optional($item->created_at)->format('Y-m-d'),
            'date' => now()->format('Y-m-d'),
            'qr_content' => $item->barcode ?: ($item->code ?: ('inventory-item:' . $item->id)),
            'barcode_content' => $item->barcode ?: ($item->code ?: ('inventory-item:' . $item->id)),
        ];
    }

    private function resolveCollectionProduct(int $collectionProductId): ?array
    {
        $collectionProduct = CollectionProduct::query()
            ->with(['product:id,name', 'customer:id,name', 'warehouse:id,name'])
            ->find($collectionProductId);

        if (! $collectionProduct) {
            return null;
        }

        return [
            'source_type' => 'collection_product',
            'source_id' => $collectionProduct->id,
            'name' => $collectionProduct->product?->name ?? 'Amostra de recolha',
            'code' => $collectionProduct->code?->code,
            'type' => 'Collection product',
            'customer' => $collectionProduct->customer?->name,
            'department' => null,
            'warehouse' => $collectionProduct->warehouse?->name,
            'lot' => $collectionProduct->lot,
            'expiry_date' => optional($collectionProduct->expiry_date)->format('Y-m-d'),
            'received_at' => optional($collectionProduct->collection_date)->format('Y-m-d'),
            'date' => now()->format('Y-m-d'),
            'qr_content' => $collectionProduct->code?->code ?: ('collection-product:' . $collectionProduct->id),
            'barcode_content' => $collectionProduct->code?->code ?: ('collection-product:' . $collectionProduct->id),
        ];
    }
}
