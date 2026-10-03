<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\AnalysisCategory;
use App\Models\CollectionProduct;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Matrix;
use App\Models\Product;
use App\Models\Profile;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Support\SampleEntryCollectionFlowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class LaboratoryIntakeSynchronizationTest extends TestCase
{
    use DatabaseTransactions;

    private VAPLab $lab;

    private Customer $customer;

    private Warehouse $warehouse;

    private Department $department;

    private Product $product;

    private Profile $profile;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);
        $this->lab = VAPLab::factory()->create();
        $this->department = Department::factory()->create();
        $this->customer = Customer::query()->create(['name' => fake()->unique()->bothify('Synchronization customer ######')]);
        $this->warehouse = Warehouse::query()->create(['name' => 'Synchronization site', 'customer_id' => $this->customer->id]);
        $matrix = Matrix::query()->create(['code' => fake()->unique()->bothify('SM-######'), 'description' => 'Synchronization matrix']);
        $this->profile = $this->createProfile($this->department);
        $matrix->profiles()->attach($this->profile);
        $this->product = Product::query()->create(['name' => 'Synchronization product', 'matrix_id' => $matrix->id]);
    }

    #[DataProvider('collectionTypes')]
    public function test_stale_models_reuse_one_persisted_graph_without_reissuing_identifiers(string $type): void
    {
        $entry = $this->entry($type);
        $staleEntry = $entry->fresh();
        $service = app(SampleEntryCollectionFlowService::class);
        $first = $service->sync($entry);
        $snapshot = $this->snapshot();
        $second = $service->sync($staleEntry);

        $this->assertSame($first->id, $second->id);
        $this->assertSame($snapshot, $this->snapshot());
        $this->assertSame($first->id, $entry->fresh()->collection_product_id);
        $this->assertSame([$this->profile->id], $first->code->analysis()->pluck('profile_id')->all());
    }

    public function test_client_supplied_resolved_scope_does_not_override_requested_profiles(): void
    {
        $foreignProfile = $this->createProfile(Department::factory()->create());
        $entry = $this->entry(payload: ['resolved_profile_ids' => [$foreignProfile->id]]);

        $record = app(SampleEntryCollectionFlowService::class)->sync($entry);

        $this->assertSame([$this->profile->id], $record->code->analysis()->pluck('profile_id')->all());
        $this->assertSame([$this->profile->id], $entry->fresh()->client_submitted_info['resolved_profile_ids']);
    }

    #[DataProvider('collectionTypes')]
    public function test_collection_dates_use_collected_at_not_the_distinct_receipt_date(string $type): void
    {
        $entry = $this->entry($type);
        $date = now()->subDays(4)->toDateString();
        $entry->update(['collected_at' => $date]);

        $record = app(SampleEntryCollectionFlowService::class)->sync($entry);

        $this->assertSame($date, $record->collection_date);
        $this->assertSame($date, $record->collection->collectionable->col_date);
        $this->assertSame([$date], $record->code->analysis()->pluck('col_date')->all());
        $this->assertSame(now()->toDateString(), $entry->fresh()->received_at->toDateString());
    }

    #[DataProvider('invalidScopes')]
    public function test_invalid_analytical_scope_is_rejected_before_any_graph_or_sequence_write(string $reason): void
    {
        $entry = $this->entry();
        $payload = $entry->client_submitted_info;

        if ($reason === 'other_matrix') {
            $payload['requested_profile_ids'] = [$this->createProfile($this->department)->id];
        } elseif ($reason === 'other_department') {
            $profile = $this->createProfile(Department::factory()->create());
            $this->product->matrix->profiles()->attach($profile);
            $payload['requested_profile_ids'] = [$profile->id];
        } elseif ($reason === 'missing_profile') {
            $payload['requested_profile_ids'] = [2147483647];
        } elseif ($reason === 'missing_product') {
            $payload['product_id'] = 2147483647;
        } elseif ($reason === 'wrong_matrix') {
            $payload['matrix_id'] = 2147483647;
        } else {
            $this->product->matrix->profiles()->detach();
        }

        $entry->update(['client_submitted_info' => $payload]);
        $field = match ($reason) {
            'missing_product' => 'client_submitted_info.product_id',
            'wrong_matrix' => 'client_submitted_info.matrix_id',
            default => 'client_submitted_info.requested_profile_ids',
        };
        $this->assertRejectedWithoutWrites($entry, $field);
    }

    public function test_another_customers_site_is_rejected_before_issuing_the_graph(): void
    {
        $otherCustomer = Customer::query()->create(['name' => 'Other synchronization customer']);
        $site = Warehouse::query()->create(['name' => 'Other site', 'customer_id' => $otherCustomer->id]);
        $entry = $this->entry();
        $entry->update(['warehouse_id' => $site->id]);

        $this->assertRejectedWithoutWrites($entry, 'warehouse_id');
    }

    #[DataProvider('ambiguousOwners')]
    public function test_linked_graph_with_another_owner_is_never_reused(string $ownership): void
    {
        $entry = $this->entry();
        $record = app(SampleEntryCollectionFlowService::class)->sync($entry);
        $other = $this->entry();
        DB::statement('SET CONSTRAINTS sample_entries_collection_product_unique DEFERRED');
        $other->update(['collection_product_id' => $record->id]);

        if ($ownership === 'foreign') {
            DB::table('sample_entries')->where('id', $other->id)->update(['lab_id' => VAPLab::factory()->create()->id]);
        } elseif ($ownership === 'unassigned') {
            DB::table('sample_entries')->where('id', $other->id)->update(['lab_id' => null]);
        } elseif ($ownership === 'archived') {
            $other->delete();
        }

        $this->assertRejectedWithoutWrites($entry->fresh(), 'collection_product_id');
    }

    public function test_missing_linked_lab_code_is_not_treated_as_a_valid_issued_graph(): void
    {
        $entry = $this->entry();
        $record = app(SampleEntryCollectionFlowService::class)->sync($entry);
        $record->code->delete();

        $this->assertRejectedWithoutWrites($entry->fresh(), 'collection_product_id');
    }

    public function test_archived_intake_replay_preserves_the_exact_existing_graph(): void
    {
        $entry = $this->entry('programmed');
        $record = app(SampleEntryCollectionFlowService::class)->sync($entry);
        CollectionProduct::query()->whereKey($record->id)->update(['deleted_at' => now()]);
        $entry->delete();
        $snapshot = $this->snapshot();

        $existing = app(SampleEntryCollectionFlowService::class)->sync($entry->fresh());

        $this->assertSame($record->id, $existing->id);
        $this->assertTrue($existing->trashed());
        $this->assertSame($snapshot, $this->snapshot());
    }

    public function test_archived_unlinked_intake_cannot_generate_a_new_graph(): void
    {
        $entry = $this->entry();
        $entry->delete();

        $this->assertRejectedWithoutWrites($entry, 'collection_product_id');
    }

    public function test_deleted_laboratory_cannot_generate_a_graph(): void
    {
        $entry = $this->entry();
        $this->lab->delete();

        $this->assertRejectedWithoutWrites($entry, 'lab_id');
    }

    public function test_intake_without_a_selected_product_remains_unlinked(): void
    {
        $entry = $this->entry(payload: ['product_id' => null]);
        $snapshot = $this->snapshot();

        $this->assertNull(app(SampleEntryCollectionFlowService::class)->sync($entry));
        $this->assertSame($snapshot, $this->snapshot());
    }

    public function test_mid_graph_failure_rolls_back_graph_links_sequences_and_activity(): void
    {
        $secondProfile = $this->createProfile($this->department);
        $this->product->matrix->profiles()->attach($secondProfile);
        $entry = $this->entry(payload: ['requested_profile_ids' => [$this->profile->id, $secondProfile->id]]);
        $snapshot = $this->snapshot();
        $created = 0;
        $event = 'eloquent.creating: '.Analysis::class;
        Event::listen($event, function () use (&$created): void {
            if (++$created === 2) {
                throw new RuntimeException('Injected second-analysis failure');
            }
        });

        try {
            app(SampleEntryCollectionFlowService::class)->sync($entry);
            $this->fail('The second analysis should fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected second-analysis failure', $exception->getMessage());
        } finally {
            Event::forget($event);
        }

        $this->assertSame($snapshot, $this->snapshot());
    }

    private function createProfile(Department $department): Profile
    {
        $category = AnalysisCategory::query()->create([
            'name' => fake()->unique()->bothify('Synchronization category ######'),
            'code' => fake()->unique()->bothify('SC-######'), 'department_id' => $department->id,
        ]);

        return Profile::query()->create([
            'name' => fake()->unique()->bothify('Synchronization profile ######'),
            'code' => fake()->unique()->bothify('SP-######'), 'category_id' => $category->id,
        ]);
    }

    /** @param array<string, mixed> $payload */
    private function entry(string $type = 'direct', array $payload = []): VAPSampleEntry
    {
        return VAPSampleEntry::factory()->create([
            'code' => null, 'lab_id' => $this->lab->id, 'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id, 'department_id' => $this->department->id,
            'client_submitted_info' => array_replace([
                'collection_type' => $type, 'product_id' => $this->product->id,
                'matrix_id' => $this->product->matrix_id, 'requested_profile_ids' => [$this->profile->id],
            ], $payload),
        ]);
    }

    private function assertRejectedWithoutWrites(VAPSampleEntry $entry, string $field): void
    {
        $snapshot = $this->snapshot();

        try {
            app(SampleEntryCollectionFlowService::class)->sync($entry);
            $this->fail('Invalid intake synchronization must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }

        $this->assertSame($snapshot, $this->snapshot());
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function snapshot(): array
    {
        $snapshot = [];

        foreach (['sample_entries', 'direct_collections', 'programmed_collections', 'collections', 'collection_product', 'lab_codes', 'samples', 'analysis', 'sequence_counters', 'activity_log'] as $table) {
            $snapshot[$table] = DB::table($table)->orderBy($table === 'sequence_counters' ? 'scope_hash' : 'id')
                ->get()->map(fn (object $row): array => (array) $row)->all();
        }

        return $snapshot;
    }

    /** @return array<string, array{string}> */
    public static function collectionTypes(): array
    {
        return ['direct' => ['direct'], 'programmed' => ['programmed']];
    }

    /** @return array<string, array{string}> */
    public static function invalidScopes(): array
    {
        return array_combine(
            ['other_matrix', 'other_department', 'missing_profile', 'missing_product', 'wrong_matrix', 'empty_scope'],
            array_map(fn (string $scope): array => [$scope], ['other_matrix', 'other_department', 'missing_profile', 'missing_product', 'wrong_matrix', 'empty_scope'])
        );
    }

    /** @return array<string, array{string}> */
    public static function ambiguousOwners(): array
    {
        return ['foreign' => ['foreign'], 'unassigned' => ['unassigned'], 'same_lab' => ['same_lab'], 'archived' => ['archived']];
    }
}
