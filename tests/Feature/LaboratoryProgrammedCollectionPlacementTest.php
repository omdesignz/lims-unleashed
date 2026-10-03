<?php

namespace Tests\Feature;

use App\Actions\PlaceProgrammedCollectionProductInAnalysis;
use App\Events\CollectionProcessed;
use App\Jobs\PlaceProductsInAnalysis;
use App\Models\Analysis;
use App\Models\AnalysisCategory;
use App\Models\Collection;
use App\Models\CollectionProduct;
use App\Models\Customer;
use App\Models\Department;
use App\Models\LabCode;
use App\Models\Matrix;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Profile;
use App\Models\ProgrammedCollection;
use App\Models\Sample;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LaboratoryProgrammedCollectionPlacementTest extends TestCase
{
    use DatabaseTransactions;

    private VAPLab $lab;

    private VAPLab $peerLab;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->lab = VAPLab::factory()->create();
        $this->peerLab = VAPLab::factory()->create();
        $this->operator = User::factory()->create(['is_active' => true]);
        $this->operator->givePermissionTo(Permission::findOrCreate('add_analysis', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $this->operator->id]);
        Notification::fake();
        Event::fake([CollectionProcessed::class]);
    }

    public function test_route_captures_lab_and_operator_and_rejects_unowned_or_peer_products(): void
    {
        $local = $this->fixture($this->lab);
        $foreign = $this->fixture($this->peerLab);
        $unowned = $this->fixture($this->lab);
        $unowned['entry']->delete();
        Bus::fake([PlaceProductsInAnalysis::class]);

        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id])
            ->post(route('programmedcollections.PlaceProductsInAnalysis', $foreign['product']))->assertNotFound();
        $this->post(route('programmedcollections.PlaceProductsInAnalysis', $unowned['product']))->assertNotFound();
        Bus::assertNotDispatched(PlaceProductsInAnalysis::class);

        $this->post(route('programmedcollections.PlaceProductsInAnalysis', $local['product']))
            ->assertRedirect()->assertSessionHasNoErrors();
        Bus::assertDispatched(PlaceProductsInAnalysis::class, fn (PlaceProductsInAnalysis $job): bool => $job->lab_id === $this->lab->id && $job->user_id === $this->operator->id
            && $job->collection_product_id === $local['product']->id
            && $job->programmed_collection_id === $local['programmed']->id && $job->afterCommit);
        $this->post(route('programmedcollections.PlaceProductsInAnalysis', $local['product']))->assertRedirect();
        Bus::assertDispatchedTimes(PlaceProductsInAnalysis::class, 1);
    }

    public function test_worker_uses_captured_lab_and_retries_preserve_analysis_and_activity(): void
    {
        $fixture = $this->fixture($this->lab);
        $job = $this->job($fixture);
        DB::table('lab_user')->insert(['lab_id' => $this->peerLab->id, 'user_id' => $this->operator->id]);
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->peerLab->id]);
        $job->handle(app(PlaceProgrammedCollectionProductInAnalysis::class));

        $sample = Sample::query()->where('cl_id', $fixture['code']->id)->sole();
        $analysis = Analysis::query()->where('sample_id', $sample->id)->sole();
        $this->assertSame($fixture['profile']->id, $analysis->profile_id);
        $this->assertSame($fixture['product']->product_id, $analysis->product_id);
        $this->assertTrue($fixture['programmed']->fresh()->placed_analysis);
        $this->assertTrue($fixture['product']->fresh()->processed);
        $this->assertSame([$sample->id], data_get($fixture['entry']->fresh()->client_submitted_info, 'linked_sample_ids'));
        Event::assertDispatchedTimes(CollectionProcessed::class, 1);
        Event::assertDispatched(CollectionProcessed::class, fn (CollectionProcessed $event): bool => $event->collectionProductId === $fixture['product']->id && $event->user->id === $this->operator->id);
        $before = $this->counts();
        $entryDate = $fixture['programmed']->fresh()->entry_date;
        $this->travel(1)->days();

        $job->handle(app(PlaceProgrammedCollectionProductInAnalysis::class));

        $this->assertSame($before, $this->counts());
        $this->assertSame($entryDate, $fixture['programmed']->fresh()->entry_date);
        Event::assertDispatchedTimes(CollectionProcessed::class, 1);
    }

    public function test_profile_selection_honors_sample_department_and_requested_profiles(): void
    {
        $fixture = $this->fixture($this->lab, selectFirstProfile: true);
        $this->addProfile($fixture['matrix'], $fixture['profile']->category_id);
        $this->addProfile($fixture['matrix'], $this->category(Department::factory()->create())->id);

        $this->job($fixture)->handle(app(PlaceProgrammedCollectionProductInAnalysis::class));

        $this->assertSame([$fixture['profile']->id], Analysis::query()->where('cl_id', $fixture['code']->id)->pluck('profile_id')->all());
    }

    public function test_archived_samples_do_not_issue_replacement_numbers_on_retry(): void
    {
        $fixture = $this->fixture($this->lab);
        $sample = Sample::query()->create(['cl_id' => $fixture['code']->id, 'sample_month' => now()->format('y/m')]);
        DB::table('samples')->where('id', $sample->id)->update(['deleted_at' => now()]);
        $before = $this->counts();

        $this->job($fixture)->handle(app(PlaceProgrammedCollectionProductInAnalysis::class));

        $this->assertSame($before, $this->counts());
        $this->assertFalse($fixture['programmed']->fresh()->placed_analysis);
        Event::assertNothingDispatched();
    }

    public function test_legacy_serialized_job_without_lab_or_operator_context_fails_closed(): void
    {
        $fixture = $this->fixture($this->lab);
        $job = $this->job($fixture);
        unset($job->lab_id, $job->user_id);
        $job = unserialize(serialize($job));
        $this->assertSame(0, $job->lab_id);
        $this->assertSame(0, $job->user_id);
        $before = $this->counts();

        try {
            $job->handle(app(PlaceProgrammedCollectionProductInAnalysis::class));
            $this->fail('A legacy job without captured ownership must fail closed.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $this->counts());
        }
    }

    #[DataProvider('invalidWorkerSources')]
    public function test_worker_rechecks_actor_and_live_lineage_without_mutation(string $change): void
    {
        $fixture = $this->fixture($this->lab);
        $job = $this->job($fixture);
        if (in_array($change, ['archived_foreign_conflict', 'archived_unassigned_conflict'], true)) {
            DB::statement('SET CONSTRAINTS sample_entries_collection_product_unique DEFERRED');
        }

        match ($change) {
            'removed_membership' => DB::table('lab_user')->where('user_id', $this->operator->id)->delete(),
            'inactive_operator' => $this->operator->update(['is_active' => false]),
            'unverified_operator' => $this->operator->update(['email_verified_at' => null]),
            'revoked_permission' => $this->operator->revokePermissionTo('add_analysis'),
            'deleted_lab' => $this->lab->delete(),
            'deleted_entry' => $fixture['entry']->delete(),
            'deleted_product' => DB::table('collection_product')->where('id', $fixture['product']->id)->update(['deleted_at' => now()]),
            'deleted_collection' => DB::table('collections')->where('id', $fixture['collection']->id)->update(['deleted_at' => now()]),
            'deleted_programmed_collection' => $fixture['programmed']->delete(),
            'deleted_code' => $fixture['code']->delete(),
            'wrong_parent' => $job->programmed_collection_id = 0,
            'wrong_lab' => $job->lab_id = $this->peerLab->id,
            'wrong_customer' => $fixture['collection']->update(['customer_id' => null]),
            'wrong_warehouse' => $fixture['collection']->update(['warehouse_id' => null]),
            'wrong_collection_type' => $fixture['collection']->update(['collectionable_type' => 'direct']),
            'archived_foreign_conflict' => VAPSampleEntry::factory()->create([
                'lab_id' => $this->peerLab->id, 'customer_id' => $fixture['entry']->customer_id,
                'collection_product_id' => $fixture['product']->id, 'deleted_at' => now(),
            ]),
            'archived_unassigned_conflict' => DB::table('sample_entries')->insert([
                'name' => 'Unassigned archived conflict', 'collection_product_id' => $fixture['product']->id,
                'deleted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]),
        };
        $before = $this->counts();

        try {
            $job->handle(app(PlaceProgrammedCollectionProductInAnalysis::class));
            $this->fail('Stale authorization or lineage must not create an analysis.');
        } catch (AuthorizationException|ModelNotFoundException|HttpException) {
            $this->assertSame($before, $this->counts());
            $this->assertFalse($fixture['programmed']->fresh()->placed_analysis);
            $this->assertFalse($fixture['product']->fresh()?->processed ?? false);
            Event::assertNothingDispatched();
        }
    }

    /** @return array<string, array{string}> */
    public static function invalidWorkerSources(): array
    {
        return collect([
            'removed_membership', 'inactive_operator', 'unverified_operator', 'revoked_permission', 'deleted_lab',
            'deleted_entry', 'deleted_product', 'deleted_collection', 'deleted_programmed_collection', 'deleted_code',
            'wrong_parent', 'wrong_lab', 'wrong_customer', 'wrong_warehouse', 'wrong_collection_type',
            'archived_foreign_conflict', 'archived_unassigned_conflict',
        ])->mapWithKeys(fn (string $change): array => [$change => [$change]])->all();
    }

    public function test_invalid_requested_profiles_leave_the_workflow_unchanged(): void
    {
        $foreign = $this->fixture($this->peerLab);
        $fixture = $this->fixture($this->lab, clientInfo: ['requested_profile_ids' => [$foreign['profile']->id]]);
        $before = $this->counts();

        try {
            $this->job($fixture)->handle(app(PlaceProgrammedCollectionProductInAnalysis::class));
            $this->fail('Invalid requested profiles must not mutate the workflow.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('client_submitted_info.requested_profile_ids', $exception->errors());
            $this->assertSame($before, $this->counts());
            $this->assertFalse($fixture['programmed']->fresh()->placed_analysis);
        }
    }

    public function test_partial_failure_rolls_back_samples_analysis_activity_and_sequence_reservations(): void
    {
        $fixture = $this->fixture($this->lab);
        $this->addProfile($fixture['matrix'], $fixture['profile']->category_id);
        $before = $this->counts();
        $counters = DB::table('sequence_counters')->orderBy('scope_hash')->get()->toArray();
        $created = 0;
        Analysis::creating(function () use (&$created): void {
            if (++$created === 2) {
                throw new RuntimeException('Simulated second analysis failure.');
            }
        });

        try {
            $this->job($fixture)->handle(app(PlaceProgrammedCollectionProductInAnalysis::class));
            $this->fail('Expected simulated persistence failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated second analysis failure.', $exception->getMessage());
        }

        $this->assertSame($before, $this->counts());
        $this->assertEquals($counters, DB::table('sequence_counters')->orderBy('scope_hash')->get()->toArray());
        $this->assertFalse($fixture['programmed']->fresh()->placed_analysis);
        $this->assertFalse($fixture['product']->fresh()->processed);
        $this->assertNull(data_get($fixture['entry']->fresh()->client_submitted_info, 'linked_sample_ids'));
        Event::assertNothingDispatched();
    }

    #[DataProvider('invalidInitialLinks')]
    public function test_first_analytical_links_reject_incomplete_or_mismatched_graphs(string $change): void
    {
        $fixture = $this->fixture($this->lab, clientInfo: $change === 'issued_collection_type_mismatch' ? ['collection_type' => 'direct'] : []);
        $sample = Sample::query()->create(['cl_id' => $fixture['code']->id, 'sample_month' => now()->format('y/m')]);
        $analysis = $this->createAnalysis($fixture, $sample);
        $links = [
            'linked_lab_code_id' => $fixture['code']->id, 'linked_sample_ids' => [$sample->id],
            'linked_collection_type' => 'programmed', 'requested_profile_ids' => [$fixture['profile']->id],
        ];
        if ($change === 'archived_conflicting_owner') {
            DB::statement('SET CONSTRAINTS sample_entries_collection_product_unique DEFERRED');
        }

        switch ($change) {
            case 'deleted_lab':
                $this->lab->delete();
                break;
            case 'deleted_entry':
                $fixture['entry']->delete();
                break;
            case 'deleted_product':
                $fixture['product']->delete();
                break;
            case 'deleted_collection':
                $fixture['collection']->delete();
                break;
            case 'deleted_subject':
                $fixture['programmed']->delete();
                break;
            case 'deleted_code':
                $fixture['code']->delete();
                break;
            case 'deleted_sample':
                $sample->delete();
                break;
            case 'deleted_analysis':
                $analysis->delete();
                break;
            case 'wrong_parent_customer':
                $fixture['collection']->update(['customer_id' => null]);
                break;
            case 'wrong_parent_site':
                $fixture['collection']->update(['warehouse_id' => null]);
                break;
            case 'wrong_analysis_product':
                $analysis->update(['product_id' => null]);
                break;
            case 'wrong_analysis_department':
                $analysis->update(['department_id' => null]);
                break;
            case 'wrong_analysis_category':
                $analysis->update(['type_id' => $this->category(Department::factory()->create())->id]);
                break;
            case 'duplicate_link_ids':
                $links['linked_sample_ids'][] = $sample->id;
                break;
            case 'duplicate_profile_analyses':
                $second = Sample::query()->create(['cl_id' => $fixture['code']->id, 'sample_month' => now()->format('y/m')]);
                $this->createAnalysis($fixture, $second);
                $links['linked_sample_ids'][] = $second->id;
                break;
            case 'unlinked_sample':
                Sample::query()->create(['cl_id' => $fixture['code']->id, 'sample_month' => now()->format('y/m')]);
                break;
            case 'foreign_code':
                $links['linked_lab_code_id'] = $this->fixture($this->peerLab)['code']->id;
                break;
            case 'extra_foreign_analysis':
                $foreign = $this->fixture($this->peerLab);
                $foreignSample = Sample::query()->create(['cl_id' => $foreign['code']->id, 'sample_month' => now()->format('y/m')]);
                $this->createAnalysis($fixture, $foreignSample);
                break;
            case 'foreign_analysis_on_local_sample':
                $foreign = $this->fixture($this->peerLab);
                $this->createAnalysis(array_replace($fixture, ['code' => $foreign['code']]), $sample);
                break;
            case 'archived_duplicate_analysis':
                $this->createAnalysis($fixture, $sample)->delete();
                break;
            case 'duplicate_code':
                LabCode::query()->create(['collection_id' => $fixture['product']->id, 'codeable_type' => 'analysis', 'cl_month' => now()->format('y/m')]);
                break;
            case 'archived_conflicting_owner':
                VAPSampleEntry::factory()->create([
                    'lab_id' => $this->peerLab->id, 'collection_product_id' => $fixture['product']->id, 'deleted_at' => now(),
                ]);
                break;
            case 'changed_canonical_product':
                $product = Product::query()->create(['name' => 'Replacement canonical product', 'matrix_id' => $fixture['matrix']->id]);
                $fixture['product']->update(['product_id' => $product->id]);
                $analysis->update(['product_id' => $product->id]);
                break;
            case 'changed_canonical_matrix':
                $matrix = Matrix::query()->create(['code' => fake()->unique()->bothify('OTHER-PLACEMENT-######')]);
                $matrix->profiles()->attach($fixture['profile']);
                Product::query()->findOrFail($fixture['product']->product_id)->update(['matrix_id' => $matrix->id]);
                break;
        }

        $before = $fixture['entry']->fresh()->getRawOriginal();

        try {
            $fixture['entry']->update(['client_submitted_info' => array_replace($fixture['entry']->client_submitted_info, $links)]);
            $this->fail('Initial links must refer to the complete live canonical graph.');
        } catch (LogicException) {
            $this->assertSame($before, $fixture['entry']->fresh()->getRawOriginal());
        }
    }

    /** @return array<string, array{string}> */
    public static function invalidInitialLinks(): array
    {
        return collect([
            'deleted_lab', 'deleted_entry', 'deleted_product', 'deleted_collection', 'deleted_subject', 'deleted_code',
            'deleted_sample', 'deleted_analysis', 'wrong_parent_customer', 'wrong_parent_site', 'wrong_analysis_product',
            'wrong_analysis_department', 'wrong_analysis_category', 'duplicate_link_ids', 'duplicate_profile_analyses',
            'unlinked_sample', 'foreign_code', 'extra_foreign_analysis', 'duplicate_code', 'archived_conflicting_owner',
            'changed_canonical_product', 'changed_canonical_matrix', 'foreign_analysis_on_local_sample',
            'archived_duplicate_analysis', 'issued_collection_type_mismatch',
        ])->mapWithKeys(fn (string $change): array => [$change => [$change]])->all();
    }

    /** @param array<string, mixed> $fixture */
    private function createAnalysis(array $fixture, Sample $sample): Analysis
    {
        return Analysis::query()->create([
            'cl_id' => $fixture['code']->id, 'sample_id' => $sample->id, 'profile_id' => $fixture['profile']->id,
            'product_id' => $fixture['product']->product_id, 'type_id' => $fixture['profile']->category_id,
            'department_id' => $fixture['entry']->department_id,
        ]);
    }

    /** @return array<string, mixed> */
    private function fixture(VAPLab $lab, bool $selectFirstProfile = false, array $clientInfo = []): array
    {
        $customer = Customer::query()->create(['name' => fake()->unique()->company()]);
        $warehouse = Warehouse::query()->create(['name' => fake()->unique()->bothify('Placement site ######'), 'customer_id' => $customer->id]);
        $department = Department::factory()->create();
        $category = $this->category($department);
        $matrix = Matrix::query()->create(['code' => fake()->unique()->bothify('PLACEMENT-######')]);
        $profile = $this->addProfile($matrix, $category->id);
        $catalogProduct = Product::query()->create(['name' => 'Placement product', 'matrix_id' => $matrix->id]);
        $programmed = ProgrammedCollection::query()->create([
            'user_id' => $this->operator->id, 'col_date' => now()->toDateString(), 'placed_analysis' => false, 'status' => false,
        ]);
        $collection = $programmed->collection()->save(new Collection([
            'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id,
        ]));
        $product = CollectionProduct::query()->create([
            'collection_id' => $collection->id, 'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id, 'product_id' => $catalogProduct->id, 'processed' => false,
        ]);
        $entry = VAPSampleEntry::factory()->create([
            'lab_id' => $lab->id, 'customer_id' => $customer->id, 'department_id' => $department->id,
            'warehouse_id' => $warehouse->id, 'collection_product_id' => $product->id,
            'client_submitted_info' => ($selectFirstProfile ? ['requested_profile_ids' => [$profile->id]] : $clientInfo)
                + ['product_id' => $catalogProduct->id, 'matrix_id' => $matrix->id, 'collection_type' => 'programmed'],
        ]);
        $code = LabCode::query()->create([
            'collection_id' => $product->id, 'codeable_type' => 'analysis', 'cl_month' => now()->format('y/m'),
        ]);

        return compact('programmed', 'collection', 'product', 'entry', 'code', 'matrix', 'profile');
    }

    private function category(Department $department): AnalysisCategory
    {
        return AnalysisCategory::query()->create([
            'name' => fake()->unique()->bothify('Placement category ######'),
            'code' => fake()->unique()->bothify('PC-######'), 'department_id' => $department->id,
        ]);
    }

    private function addProfile(Matrix $matrix, int $categoryId): Profile
    {
        $profile = Profile::query()->create([
            'name' => fake()->unique()->bothify('Placement profile ######'),
            'code' => fake()->unique()->bothify('PP-######'), 'category_id' => $categoryId,
        ]);
        $matrix->profiles()->attach($profile->id);

        return $profile;
    }

    /** @param array<string, mixed> $fixture */
    private function job(array $fixture): PlaceProductsInAnalysis
    {
        return new PlaceProductsInAnalysis($fixture['programmed']->id, $fixture['product']->id, $this->operator->id, $this->lab->id);
    }

    /** @return array<string, int> */
    private function counts(): array
    {
        return collect(['samples', 'analysis', 'activity_log', 'sequence_counters'])
            ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])->all();
    }
}
