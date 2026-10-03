<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\InventoryItemDocumentMedia;
use App\Models\VAPLab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItemDocumentMedia>
 */
class InventoryItemDocumentMediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'model_type' => (new InventoryItem)->getMorphClass(),
            'model_id' => fn (): int => InventoryItem::query()->create([
                'lab_id' => VAPLab::factory()->create()->id,
                'name' => fake()->words(3, true),
            ])->id,
            'uuid' => fake()->uuid(),
            'collection_name' => 'documents',
            'name' => 'Retained item document',
            'file_name' => 'retained.pdf',
            'disk' => 'public',
            'conversions_disk' => 'public',
            'mime_type' => 'application/pdf',
            'size' => 1,
            'order_column' => 1,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
        ];
    }
}
