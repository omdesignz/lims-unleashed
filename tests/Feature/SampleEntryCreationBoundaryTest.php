<?php

namespace Tests\Feature;

use App\Models\AnalysisCategory;
use App\Models\Customer;
use App\Models\CustomerRequest;
use App\Models\Department;
use App\Models\Matrix;
use App\Models\Parameter;
use App\Models\PersonnelQualification;
use App\Models\Product;
use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Support\SampleEntryValidation;
use Closure;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SampleEntryCreationBoundaryTest extends TestCase
{
    use DatabaseTransactions;

    private User $operator;

    private VAPLab $lab;

    private Customer $customer;

    private Warehouse $warehouse;

    private Department $department;

    private Product $product;

    private Profile $profile;

    private Profile $additionalProfile;

    private PersonnelQualification $qualification;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Notification::fake();
        Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);
        $this->operator = User::factory()->create(['is_active' => true]);
        $this->operator->assignRole(Role::findOrCreate('admin', 'web'));
        $this->lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['user_id' => $this->operator->id, 'lab_id' => $this->lab->id]);
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
        $this->customer = Customer::query()->create(['name' => fake()->unique()->bothify('Correction customer ######')]);
        $this->warehouse = Warehouse::query()->create(['name' => 'Correction site', 'customer_id' => $this->customer->id]);
        $this->department = Department::factory()->create();
        $this->qualification = PersonnelQualification::query()->create([
            'lab_id' => $this->lab->id,
            'user_id' => $this->operator->id, 'qualified_by_id' => $this->operator->id,
            'department_id' => $this->department->id, 'capability' => 'sample_intake_validation',
            'authorized_from' => now()->subDay(), 'authorized_until' => now()->addYear(),
            'training_completed_at' => now()->subDay(), 'training_reference' => 'INTAKE-CORRECTION', 'is_active' => true,
        ]);
        $category = AnalysisCategory::query()->create([
            'name' => 'Correction category', 'code' => fake()->unique()->bothify('CC-######'), 'department_id' => $this->department->id,
        ]);
        $matrix = Matrix::query()->create(['code' => fake()->unique()->bothify('CM-######'), 'description' => 'Correction matrix']);
        $this->profile = Profile::query()->create(['name' => 'Issued profile', 'code' => fake()->unique()->bothify('CP-######'), 'category_id' => $category->id]);
        $this->additionalProfile = Profile::query()->create(['name' => 'Additional profile', 'code' => fake()->unique()->bothify('CP-######'), 'category_id' => $category->id]);
        $matrix->profiles()->attach([$this->profile->id, $this->additionalProfile->id]);
        $parameter = Parameter::query()->create(['name' => 'Issued parameter', 'active' => true]);
        $this->profile->parameters()->attach($parameter);
        $this->product = Product::query()->create(['name' => 'Correction product', 'matrix_id' => $matrix->id]);
    }

    #[DataProvider('invalidInputs')]
    public function test_every_entrypoint_enforces_the_same_metadata_limits(string $mode, string $field, mixed $invalid): void
    {
        $payload = $this->creationPayload();
        data_set($payload, $field, $invalid);
        $before = VAPSampleEntry::query()->count();

        $this->submit($mode, $payload)->assertUnprocessable()->assertJsonValidationErrors($this->errorField($mode, $field));
        $this->assertSame($before, VAPSampleEntry::query()->count());
    }

    public static function invalidInputs(): array
    {
        $cases = [];

        foreach (['single', 'manual', 'spreadsheet'] as $mode) {
            foreach ([
                'name' => str_repeat('N', 256),
                'client_submitted_info.lot' => str_repeat('L', 256),
                'client_submitted_info.quantity' => str_repeat('Q', 256),
                'client_submitted_info.chain_of_custody_notes' => str_repeat('Ç', 2001),
                'client_submitted_info.quality_control_purpose' => 'FORGED-PURPOSE',
            ] as $field => $invalid) {
                $cases[$mode.' '.$field] = [$mode, $field, $invalid];
            }
        }

        return $cases;
    }

    #[DataProvider('entryModes')]
    public function test_valid_metadata_at_the_limits_survives_every_entrypoint(string $mode): void
    {
        $payload = $this->creationPayload();
        $payload['client_submitted_info']['lot'] = str_repeat('L', 255);
        $payload['client_submitted_info']['quantity'] = '2 kg';
        $payload['client_submitted_info']['chain_of_custody_notes'] = str_repeat('Ç', 2000);

        $this->submit($mode, $payload)->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('type', 'success');
        $entry = VAPSampleEntry::query()->where('name', $payload['name'])->firstOrFail();
        $this->assertSame($payload['client_submitted_info']['lot'], $entry->collectionProduct->lot);
        $this->assertSame('2 kg', $entry->collectionProduct->qty);
        $this->assertSame($this->product->name, $entry->client_submitted_info['product_name'] ?? null);
        $this->assertSame($this->product->name, $entry->collectionProduct->comercial_brand);
        $this->assertSame($payload['client_submitted_info']['chain_of_custody_notes'], $entry->collectionProduct->customer_submitted_info);

        if ($mode === 'spreadsheet') {
            $this->assertTrue($entry->client_submitted_info['imported_from_spreadsheet']);
            $this->assertSame($this->operator->id, $entry->client_submitted_info['imported_by_id']);
        }
    }

    public static function entryModes(): array
    {
        return ['single' => ['single'], 'manual' => ['manual'], 'spreadsheet' => ['spreadsheet']];
    }

    #[DataProvider('entryModes')]
    public function test_matrix_mismatch_is_rejected_before_preparation_can_replace_it(string $mode): void
    {
        $matrix = Matrix::query()->create(['code' => 'OTHER-CREATION-MATRIX', 'description' => 'Mismatched matrix']);
        $payload = $this->creationPayload();
        $payload['client_submitted_info']['matrix_id'] = $matrix->id;
        $before = VAPSampleEntry::query()->count();

        $this->submit($mode, $payload)->assertUnprocessable()
            ->assertJsonValidationErrors($this->errorField($mode, 'client_submitted_info.matrix_id'));
        $this->assertSame($before, VAPSampleEntry::query()->count());
    }

    public function test_manual_batch_rejects_non_array_metadata_without_a_server_error(): void
    {
        $before = VAPSampleEntry::query()->count();
        $this->submit('manual', array_replace($this->creationPayload(), ['client_submitted_info' => 'NOT-AN-ARRAY']))
            ->assertUnprocessable()->assertJsonValidationErrors('samples');
        $this->assertSame($before, VAPSampleEntry::query()->count());
    }

    public function test_manual_batch_discards_unvalidated_root_attribution_and_sequence_fields(): void
    {
        $payload = array_replace($this->creationPayload(), [
            'code' => 'CLIENT-MANUAL-CODE', 'seq' => 777, 'sample_year' => '1999',
            'retention_status' => 'discarded', 'received_by_id' => 2147483647, 'received_by_label' => 'Forged operator',
        ]);

        $this->submit('manual', $payload)->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('type', 'success');
        $entry = VAPSampleEntry::query()->where('code', 'CLIENT-MANUAL-CODE')->firstOrFail();
        $this->assertNull($entry->seq);
        $this->assertSame(now()->format('Y'), $entry->sample_year);
        $this->assertSame($this->operator->id, $entry->received_by_id);
        $this->assertSame($this->operator->name, $entry->received_by_label);
        $this->assertNotSame('discarded', $entry->retention_status);
        $this->assertTrue($entry->client_submitted_info['manual_batch']);
        $this->assertSame($this->operator->id, $entry->client_submitted_info['manual_batch_registered_by_id']);
    }

    #[DataProvider('protectedMetadata')]
    public function test_manual_batch_rejects_forged_system_evidence(string $field, mixed $value): void
    {
        $payload = $this->creationPayload();
        data_set($payload, 'client_submitted_info.'.$field, $value);
        $before = VAPSampleEntry::query()->count();

        $this->submit('manual', $payload)->assertUnprocessable()->assertJsonValidationErrors('samples');
        $this->assertSame($before, VAPSampleEntry::query()->count());
    }

    public static function protectedMetadata(): array
    {
        return [
            'analytical link' => ['linked_sample_ids', [2147483647]],
            'release evidence' => ['qc_release', ['decision' => 'released']],
            'batch attribution' => ['manual_batch_registered_by_id', 2147483647],
            'import actor' => ['imported_by_id', 2147483647],
            'import timestamp' => ['imported_at', '1999-01-01T00:00:00Z'],
            'import source' => ['imported_from_spreadsheet', true],
        ];
    }

    public function test_unlinked_manual_intake_cannot_use_another_customers_site(): void
    {
        $customer = Customer::query()->create(['name' => 'Different shared customer']);
        $site = Warehouse::query()->create(['name' => 'Different site', 'customer_id' => $customer->id]);
        $payload = array_replace($this->creationPayload(), ['warehouse_id' => $site->id, 'client_submitted_info' => []]);
        $before = VAPSampleEntry::query()->count();

        $this->submit('manual', $payload)->assertUnprocessable()->assertJsonValidationErrors('samples');
        $this->assertSame($before, VAPSampleEntry::query()->count());
    }

    public function test_invalid_later_batch_row_rejects_every_row_before_writing(): void
    {
        $payload = $this->creationPayload();
        $second = $payload;
        $second['client_submitted_info']['lot'] = str_repeat('L', 256);
        $before = VAPSampleEntry::query()->count();

        $this->postJson(route('vap_samples.samples.bulk-store'), ['samples' => [$payload, $second]])
            ->assertUnprocessable()->assertJsonValidationErrors('samples');
        $this->assertSame($before, VAPSampleEntry::query()->count());
    }

    public function test_name_validation_uses_the_visible_portuguese_field_label(): void
    {
        $response = $this->submit('single', array_replace($this->creationPayload(), ['name' => str_repeat('N', 256)]));

        $response->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->assertStringContainsString('nome da amostra', $response->json('errors.name.0'));
    }

    #[DataProvider('malformedIdentifiers')]
    public function test_malformed_identifiers_fail_validation_before_postgresql_queries(string $mode, string $field, mixed $value): void
    {
        $payload = $this->creationPayload();
        data_set($payload, $field, $value);
        $before = VAPSampleEntry::query()->count();
        $this->submit($mode, $payload)->assertUnprocessable()->assertJsonValidationErrors($this->errorField($mode, $field));
        $this->assertSame($before, VAPSampleEntry::query()->count());
    }

    public static function malformedIdentifiers(): array
    {
        $cases = [];

        foreach (['single', 'manual'] as $mode) {
            foreach (['customer_id', 'warehouse_id', 'department_id', 'proposal_id', 'packaging_id', 'portal_request_id', 'client_submitted_info.product_id', 'client_submitted_info.matrix_id', 'client_submitted_info.requested_profile_ids.0'] as $field) {
                foreach (['text' => 'not-an-id', 'array' => [1]] as $shape => $value) {
                    $cases[$mode.' '.$field.' '.$shape] = [$mode, $field, $value];
                }
            }
        }

        return $cases;
    }

    public function test_manual_portal_batch_preserves_the_selected_row_and_derives_its_evidence(): void
    {
        $payload = $this->creationPayload();
        $request = CustomerRequest::query()->create([
            'lab_id' => $this->lab->id,
            'reference' => 'CREATION-PORTAL-BATCH', 'title' => 'Portal batch', 'request_type' => 'analysis_request',
            'customer_id' => $this->customer->id, 'warehouse_id' => $this->warehouse->id,
            'extra_data' => [
                'product_id' => $this->product->id, 'matrix_id' => $this->product->matrix_id,
                'requested_profiles' => [$this->profile->id], 'lot' => 'HEADER-LOT', 'quantity' => '9 kg',
                'samples' => [
                    ['batch_index' => 3, 'sample_name' => 'First portal row', 'lot' => 'FIRST-LOT'],
                    ['batch_index' => 7, 'sample_name' => 'Selected portal row', 'lot' => 'SELECTED-LOT', 'quantity' => '2 kg'],
                ],
            ],
        ]);
        $payload['portal_request_id'] = $request->id;
        $payload['customer_request_id'] = $request->id;
        $payload['client_submitted_info']['batch_sample_index'] = 7;
        $payload['client_submitted_info']['batch_sample'] = ['batch_index' => 999, 'sample_name' => 'Forged evidence'];

        $this->submit('manual', $payload)->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('type', 'success');
        $entry = VAPSampleEntry::query()->where('name', $payload['name'])->firstOrFail();
        $this->assertSame(7, $entry->client_submitted_info['batch_sample_index'] ?? null);
        $this->assertSame('Selected portal row', data_get($entry->client_submitted_info, 'batch_sample.sample_name'));
        $this->assertSame('SELECTED-LOT', $entry->client_submitted_info['lot']);
        $this->assertSame('SELECTED-LOT', $entry->collectionProduct->lot);
        $this->assertSame('2 kg', $entry->collectionProduct->qty);
        $this->assertSame([7], data_get($request->fresh()->extra_data, 'validated_batch_indexes'));
        $this->assertSame($entry->id, data_get($request->fresh()->extra_data, 'validated_sample_entry_id'));
    }

    public function test_portal_row_selector_requires_an_owned_source_row(): void
    {
        $request = CustomerRequest::query()->create([
            'lab_id' => $this->lab->id,
            'reference' => 'CREATION-PORTAL-SOURCE', 'title' => 'Portal source',
            'customer_id' => $this->customer->id, 'warehouse_id' => $this->warehouse->id,
            'extra_data' => ['samples' => [['batch_index' => 3, 'sample_name' => 'Only portal row']]],
        ]);
        $before = VAPSampleEntry::query()->count();
        $payload = $this->creationPayload();
        $payload['portal_request_id'] = $request->id;
        $payload['client_submitted_info']['batch_sample_index'] = 7;
        $this->submit('single', $payload)->assertUnprocessable()->assertJsonValidationErrors('client_submitted_info.batch_sample_index');
        unset($payload['portal_request_id']);
        $this->submit('manual', $payload)->assertUnprocessable()->assertJsonValidationErrors('samples');
        $this->assertSame($before, VAPSampleEntry::query()->count());
        $this->assertNull(data_get($request->fresh()->extra_data, 'validated_sample_entry_id'));
    }

    #[DataProvider('portalReplayModes')]
    public function test_a_registered_portal_row_cannot_issue_another_sample(string $mode): void
    {
        $request = $this->portalRequestForReplay();
        $payload = $this->creationPayload();
        $payload['portal_request_id'] = $request->id;
        $payload['client_submitted_info']['batch_sample_index'] = 7;
        $this->submit('single', $payload)->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('type', 'success');
        $before = VAPSampleEntry::query()->count();
        $counters = DB::table('sequence_counters')->orderBy('scope_hash')->get()->toJson();
        $validated = $request->fresh()->extra_data->all();

        $this->submit($mode, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('client_submitted_info.batch_sample_index');

        $this->assertSame($before, VAPSampleEntry::query()->count());
        $this->assertSame($counters, DB::table('sequence_counters')->orderBy('scope_hash')->get()->toJson());
        $this->assertSame($validated, $request->fresh()->extra_data->all());
    }

    /** @return array<string, array{string}> */
    public static function portalReplayModes(): array
    {
        return ['single' => ['single'], 'manual' => ['manual']];
    }

    public function test_duplicate_portal_row_in_one_manual_batch_rolls_back_the_first_sample_and_sequences(): void
    {
        $request = $this->portalRequestForReplay();
        $payload = $this->creationPayload();
        $payload['portal_request_id'] = $request->id;
        $payload['client_submitted_info']['batch_sample_index'] = 7;
        $second = array_replace($payload, ['name' => 'Repeated row in the same batch']);
        $before = VAPSampleEntry::query()->count();
        $counters = DB::table('sequence_counters')->orderBy('scope_hash')->get()->toJson();

        $this->postJson(route('vap_samples.samples.bulk-store'), ['samples' => [$payload, $second]])
            ->assertUnprocessable()->assertJsonValidationErrors('client_submitted_info.batch_sample_index');

        $this->assertSame($before, VAPSampleEntry::query()->count());
        $this->assertSame($counters, DB::table('sequence_counters')->orderBy('scope_hash')->get()->toJson());
        $this->assertNull(data_get($request->fresh()->extra_data, 'validated_sample_entry_id'));
        $this->assertSame([], data_get($request->fresh()->extra_data, 'validated_batch_indexes', []));
    }

    public function test_completed_portal_request_cannot_be_used_to_issue_new_identifiers(): void
    {
        $request = $this->portalRequestForReplay();
        $request->update(['status' => 'completed']);
        $payload = $this->creationPayload();
        $payload['portal_request_id'] = $request->id;
        $payload['client_submitted_info']['batch_sample_index'] = 7;
        $before = VAPSampleEntry::query()->count();

        $this->submit('single', $payload)->assertUnprocessable()->assertJsonValidationErrors('portal_request_id');
        $this->assertSame($before, VAPSampleEntry::query()->count());
        $this->assertNull(data_get($request->fresh()->extra_data, 'validated_sample_entry_id'));
    }

    #[DataProvider('staleActorChanges')]
    public function test_manual_batch_rechecks_actor_after_row_validation(string $change): void
    {
        $this->mutateAfterBatchValidation(function () use ($change): void {
            if ($change === 'membership') {
                DB::table('lab_user')->where('user_id', $this->operator->id)->where('lab_id', $this->lab->id)->delete();
            } else {
                DB::table('users')->where('id', $this->operator->id)->update(['is_active' => false]);
            }
        });
        $before = VAPSampleEntry::query()->count();
        $counters = DB::table('sequence_counters')->orderBy('scope_hash')->get()->toJson();

        $this->submit('manual', $this->creationPayload())->assertForbidden();

        $this->assertSame($before, VAPSampleEntry::query()->count());
        $this->assertSame($counters, DB::table('sequence_counters')->orderBy('scope_hash')->get()->toJson());
    }

    /** @return array<string, array{string}> */
    public static function staleActorChanges(): array
    {
        return ['revoked membership' => ['membership'], 'inactive operator' => ['inactive']];
    }

    #[DataProvider('entryModes')]
    public function test_revoked_qualification_returns_forbidden_without_issuing_identifiers(string $mode): void
    {
        $revoked = false;
        DB::connection()->beforeExecuting(function (string $query) use (&$revoked): void {
            if (! $revoked && str_contains($query, 'from "lab_user"') && str_contains($query, 'for update')) {
                $revoked = true;
                DB::table('personnel_qualifications')->where('id', $this->qualification->id)->update(['is_active' => false]);
            }
        });
        $before = VAPSampleEntry::query()->count();
        $counters = DB::table('sequence_counters')->orderBy('scope_hash')->get()->toJson();

        $this->submit($mode, $this->creationPayload())->assertForbidden();

        $this->assertTrue($revoked);
        $this->assertSame($before, VAPSampleEntry::query()->count());
        $this->assertSame($counters, DB::table('sequence_counters')->orderBy('scope_hash')->get()->toJson());
    }

    public function test_manual_batch_rechecks_portal_customer_site_after_row_validation(): void
    {
        $request = $this->portalRequestForReplay();
        $otherSite = Warehouse::query()->create(['name' => 'Reassigned source site', 'customer_id' => $this->customer->id]);
        $this->mutateAfterBatchValidation(function () use ($request, $otherSite): void {
            DB::table('customer_requests')->where('id', $request->id)->update(['warehouse_id' => $otherSite->id]);
        });
        $payload = $this->creationPayload();
        $payload['portal_request_id'] = $request->id;
        $payload['client_submitted_info']['batch_sample_index'] = 7;
        $before = VAPSampleEntry::query()->count();
        $counters = DB::table('sequence_counters')->orderBy('scope_hash')->get()->toJson();

        $this->submit('manual', $payload)->assertUnprocessable()->assertJsonValidationErrors('portal_request_id');

        $this->assertSame($before, VAPSampleEntry::query()->count());
        $this->assertSame($counters, DB::table('sequence_counters')->orderBy('scope_hash')->get()->toJson());
        $this->assertNull(data_get($request->fresh()->extra_data, 'validated_sample_entry_id'));
    }

    private function mutateAfterBatchValidation(Closure $mutation): void
    {
        $this->app->instance(SampleEntryValidation::class, new class($mutation) extends SampleEntryValidation
        {
            private bool $mutated = false;

            public function __construct(private readonly Closure $mutation) {}

            public function validate(array $input, int $labId, ?VAPSampleEntry $sampleEntry = null): array
            {
                $validated = parent::validate($input, $labId, $sampleEntry);

                if (! $this->mutated) {
                    ($this->mutation)();
                    $this->mutated = true;
                }

                return $validated;
            }
        });
    }

    private function portalRequestForReplay(): CustomerRequest
    {
        return CustomerRequest::query()->create([
            'lab_id' => $this->lab->id,
            'reference' => fake()->unique()->bothify('REPLAY-PORTAL-######'), 'title' => 'Replay source',
            'customer_id' => $this->customer->id, 'warehouse_id' => $this->warehouse->id,
            'extra_data' => [
                'product_id' => $this->product->id, 'matrix_id' => $this->product->matrix_id,
                'requested_profiles' => [$this->profile->id],
                'samples' => [['batch_index' => 7, 'sample_name' => 'One portal row', 'lot' => 'REPLAY-LOT']],
            ],
        ]);
    }

    public function test_selected_portal_row_supplies_catalogue_and_metadata_when_header_fields_are_empty(): void
    {
        $request = CustomerRequest::query()->create([
            'lab_id' => $this->lab->id,
            'reference' => 'ROW-CATALOGUE-SOURCE', 'title' => 'Row catalogue source',
            'customer_id' => $this->customer->id, 'warehouse_id' => $this->warehouse->id,
            'extra_data' => [
                'product_id' => null, 'matrix_id' => null, 'lot' => null, 'quantity' => null,
                'requested_profiles' => [$this->profile->id],
                'samples' => [[
                    'batch_index' => 7, 'sample_name' => 'Selected row', 'product_id' => $this->product->id,
                    'matrix_id' => $this->product->matrix_id, 'lot' => 'ROW-LOT', 'quantity' => 0,
                ]],
            ],
        ]);
        $payload = $this->creationPayload();
        unset($payload['client_submitted_info']['product_id'], $payload['client_submitted_info']['matrix_id']);
        $payload['portal_request_id'] = $request->id;
        $payload['client_submitted_info']['batch_sample_index'] = 7;

        $this->submit('single', $payload)->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('type', 'success');

        $entry = VAPSampleEntry::query()->where('name', $payload['name'])->sole();
        $this->assertSame($this->product->id, $entry->client_submitted_info['product_id']);
        $this->assertSame($this->product->matrix_id, $entry->client_submitted_info['matrix_id']);
        $this->assertSame('ROW-LOT', $entry->collectionProduct->lot);
        $this->assertSame('0', $entry->client_submitted_info['quantity']);
        $this->assertSame('0', $entry->collectionProduct->qty);
        $this->assertSame([$this->profile->id], $entry->collectionProduct->code->analysis()->pluck('profile_id')->all());
    }

    public function test_empty_portal_row_fields_fall_back_to_validated_header_values_without_rewriting_evidence(): void
    {
        $row = ['batch_index' => 7, 'sample_name' => 'Fallback row', 'product_id' => null, 'matrix_id' => null, 'lot' => '', 'quantity' => null];
        $request = CustomerRequest::query()->create([
            'lab_id' => $this->lab->id,
            'reference' => 'HEADER-FALLBACK-SOURCE', 'title' => 'Header fallback source',
            'customer_id' => $this->customer->id, 'warehouse_id' => $this->warehouse->id,
            'extra_data' => [
                'product_id' => $this->product->id, 'matrix_id' => $this->product->matrix_id,
                'requested_profiles' => [$this->profile->id], 'lot' => 'HEADER-LOT', 'quantity' => '4 kg', 'samples' => [$row],
            ],
        ]);
        $payload = $this->creationPayload();
        $payload['portal_request_id'] = $request->id;
        $payload['client_submitted_info']['batch_sample_index'] = 7;

        $this->submit('single', $payload)->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('type', 'success');

        $entry = VAPSampleEntry::query()->where('name', $payload['name'])->sole();
        $this->assertSame($row, $entry->client_submitted_info['batch_sample']);
        $this->assertSame('HEADER-LOT', $entry->collectionProduct->lot);
        $this->assertSame('4 kg', $entry->collectionProduct->qty);
    }

    #[DataProvider('invalidPortalRowMetadata')]
    public function test_server_derived_portal_row_is_revalidated_before_issuing_identifiers(string $field, mixed $value): void
    {
        $row = [
            'batch_index' => 7, 'sample_name' => 'Invalid selected row', 'product_id' => $this->product->id,
            'matrix_id' => $this->product->matrix_id, 'lot' => 'ROW-LOT', 'quantity' => '1 kg',
        ];
        $row[$field] = $value === 'other-existing-matrix'
            ? Matrix::query()->create(['code' => fake()->unique()->bothify('MISMATCHED-ROW-######')])->id
            : $value;
        $request = CustomerRequest::query()->create([
            'lab_id' => $this->lab->id,
            'reference' => 'INVALID-ROW-SOURCE', 'title' => 'Invalid row source',
            'customer_id' => $this->customer->id, 'warehouse_id' => $this->warehouse->id,
            'extra_data' => ['requested_profiles' => [$this->profile->id], 'samples' => [$row]],
        ]);
        $payload = $this->creationPayload();
        $payload['portal_request_id'] = $request->id;
        $payload['client_submitted_info']['batch_sample_index'] = 7;
        $before = VAPSampleEntry::query()->count();
        $counters = DB::table('sequence_counters')->orderBy('scope_hash')->get()->toJson();

        $this->submit('single', $payload)->assertUnprocessable()->assertJsonValidationErrors('client_submitted_info.'.$field);

        $this->assertSame($before, VAPSampleEntry::query()->count());
        $this->assertSame($counters, DB::table('sequence_counters')->orderBy('scope_hash')->get()->toJson());
        $this->assertNull(data_get($request->fresh()->extra_data, 'validated_sample_entry_id'));
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidPortalRowMetadata(): array
    {
        return [
            'oversized lot' => ['lot', str_repeat('L', 256)],
            'malformed product' => ['product_id', 'not-an-id'],
            'missing matrix' => ['matrix_id', 2147483647],
            'incompatible matrix' => ['matrix_id', 'other-existing-matrix'],
            'non scalar quantity' => ['quantity', ['forged' => '1 kg']],
        ];
    }

    public function test_conflicting_portal_request_aliases_are_rejected_without_writes(): void
    {
        $attributes = ['lab_id' => $this->lab->id, 'title' => 'Alias source', 'customer_id' => $this->customer->id, 'warehouse_id' => $this->warehouse->id];
        $first = CustomerRequest::query()->create($attributes + ['reference' => 'FIRST-ALIAS-SOURCE']);
        $second = CustomerRequest::query()->create($attributes + ['reference' => 'SECOND-ALIAS-SOURCE']);
        $payload = $this->creationPayload();
        $payload['portal_request_id'] = $first->id;
        $payload['customer_request_id'] = $second->id;
        $before = VAPSampleEntry::query()->count();

        $this->submit('single', $payload)->assertUnprocessable()->assertJsonValidationErrors('customer_request_id');
        $this->submit('manual', $payload)->assertUnprocessable()->assertJsonValidationErrors('samples');

        $this->assertSame($before, VAPSampleEntry::query()->count());
    }

    public function test_portal_source_from_another_lab_is_not_available_to_intake(): void
    {
        $request = CustomerRequest::query()->create([
            'lab_id' => VAPLab::factory()->create()->id, 'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id, 'title' => 'Private peer-lab request', 'status' => 'pending',
        ]);
        $payload = $this->creationPayload();
        $payload['portal_request_id'] = $request->id;
        $before = VAPSampleEntry::query()->count();
        $this->postJson(route('vap_samples.samples.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('portal_request_id');
        $this->assertSame($before, VAPSampleEntry::query()->count());
    }

    public function test_portal_aliases_cannot_select_another_customer_site_source(): void
    {
        $otherCustomer = Customer::query()->create(['name' => 'Other portal customer']);
        $otherSite = Warehouse::query()->create(['name' => 'Other portal site', 'customer_id' => $otherCustomer->id]);
        $request = CustomerRequest::query()->create([
            'lab_id' => $this->lab->id,
            'reference' => 'OTHER-SITE-SOURCE', 'title' => 'Other site source',
            'customer_id' => $otherCustomer->id, 'warehouse_id' => $otherSite->id,
        ]);
        $before = VAPSampleEntry::query()->count();

        foreach (['portal_request_id', 'customer_request_id'] as $alias) {
            $payload = $this->creationPayload() + [$alias => $request->id];
            $this->submit('single', $payload)->assertUnprocessable()->assertJsonValidationErrors($alias);
            $this->submit('manual', $payload)->assertUnprocessable()->assertJsonValidationErrors('samples');
        }

        $this->assertSame($before, VAPSampleEntry::query()->count());
        $this->assertNull(data_get($request->fresh()->extra_data, 'validated_sample_entry_id'));
    }

    public function test_duplicate_explicit_codes_in_manual_batch_fail_before_any_writes(): void
    {
        $payload = $this->creationPayload();
        $payload['code'] = 'DUPLICATE-BATCH-CODE';
        $before = VAPSampleEntry::query()->count();
        $this->postJson(route('vap_samples.samples.bulk-store'), ['samples' => [$payload, $payload]])
            ->assertUnprocessable()->assertJsonValidationErrors('samples');
        $this->assertSame($before, VAPSampleEntry::query()->count());
    }

    public function test_duplicate_explicit_codes_in_spreadsheet_fail_before_any_writes(): void
    {
        $payload = $this->creationPayload();
        $payload['code'] = 'DUPLICATE-SPREADSHEET-CODE';
        $csv = $this->csv($payload);
        $csv .= explode("\n", $csv)[1]."\n";
        $before = VAPSampleEntry::query()->count();
        $this->postJson(route('vap_samples.samples.import'), [
            'file' => UploadedFile::fake()->createWithContent('duplicate-codes.csv', $csv),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->assertSame($before, VAPSampleEntry::query()->count());
    }

    private function creationPayload(): array
    {
        return [
            'name' => 'Creation boundary sample', 'sample_type' => 'ROTINA', 'lab_id' => $this->lab->id,
            'customer_id' => $this->customer->id, 'warehouse_id' => $this->warehouse->id, 'department_id' => $this->department->id,
            'received_at' => now()->toDateTimeString(), 'status' => 'POR_INICIAR',
            'client_submitted_info' => [
                'product_id' => $this->product->id, 'product_name' => $this->product->name, 'matrix_id' => $this->product->matrix_id,
                'requested_profile_ids' => [$this->profile->id], 'collection_type' => 'direct', 'request_origin' => 'client',
            ],
        ];
    }

    private function errorField(string $mode, string $field): string
    {
        return match ($mode) {
            'manual' => 'samples',
            'spreadsheet' => 'file',
            default => $field,
        };
    }

    private function submit(string $mode, array $payload): TestResponse
    {
        if ($mode === 'single') {
            return $this->postJson(route('vap_samples.samples.store'), $payload);
        }

        if ($mode === 'manual') {
            return $this->postJson(route('vap_samples.samples.bulk-store'), ['samples' => [$payload]]);
        }

        return $this->postJson(route('vap_samples.samples.import'), [
            'file' => UploadedFile::fake()->createWithContent('creation-boundary.csv', $this->csv($payload)),
        ]);
    }

    private function csv(array $payload): string
    {
        $row = array_merge(array_diff_key($payload, ['client_submitted_info' => true]), $payload['client_submitted_info']);
        $row['requested_profile_ids'] = implode(';', $row['requested_profile_ids'] ?? []);

        return implode(',', array_keys($row))."\n".implode(',', array_map(fn (mixed $value): string => '"'.str_replace('"', '""', (string) $value).'"', $row))."\n";
    }
}
