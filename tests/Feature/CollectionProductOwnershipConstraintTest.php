<?php

namespace Tests\Feature;

use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CollectionProductOwnershipConstraintTest extends TestCase
{
    use DatabaseTransactions;

    public function test_one_collection_product_cannot_be_owned_by_two_entries_even_if_the_first_is_archived(): void
    {
        $productId = $this->collectionProductId();
        $owner = VAPSampleEntry::factory()->create(['collection_product_id' => $productId]);
        $otherLab = VAPLab::factory()->create();

        $this->assertDuplicateOwnerRejected($productId, $otherLab->id);

        $owner->delete();

        $this->assertDuplicateOwnerRejected($productId, $otherLab->id);
        $this->assertSame(1, VAPSampleEntry::withTrashed()->where('collection_product_id', $productId)->count());
    }

    public function test_unlinked_entries_and_distinct_collection_products_remain_valid(): void
    {
        $first = VAPSampleEntry::factory()->create();
        $second = VAPSampleEntry::factory()->create();
        $firstProductId = $this->collectionProductId();
        $secondProductId = $this->collectionProductId();

        $first->update(['collection_product_id' => $firstProductId]);
        $second->update(['collection_product_id' => $secondProductId]);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame($firstProductId, $first->fresh()->collection_product_id);
        $this->assertSame($secondProductId, $second->fresh()->collection_product_id);
    }

    private function collectionProductId(): int
    {
        return DB::table('collection_product')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assertDuplicateOwnerRejected(int $productId, int $otherLabId): void
    {
        try {
            DB::transaction(function () use ($productId, $otherLabId): void {
                VAPSampleEntry::factory()->create([
                    'collection_product_id' => $productId,
                    'lab_id' => $otherLabId,
                ]);
            });

            $this->fail('A collection row cannot have a second intake owner.');
        } catch (QueryException $exception) {
            $this->assertSame('23505', $exception->getCode());
        }
    }
}
