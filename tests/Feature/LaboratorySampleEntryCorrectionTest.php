<?php

namespace Tests\Feature;

use App\Actions\UpdateCollectionAccession;
use App\Actions\UpdateSampleEntry;
use App\Models\Analysis;
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
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LaboratorySampleEntryCorrectionTest extends TestCase
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

    #[DataProvider('collectionTypes')]
    public function test_metadata_correction_keeps_issued_identity_scope_and_graph_identifiers(string $type): void
    {
        $entry = $this->intake($type);
        $identity = $this->issuedIdentity($entry);
        $before = $entry->client_submitted_info;
        $record = $entry->collectionProduct;
        $submittedPayload = $record->extra_data->get('submitted_payload');
        $date = now()->subDays(3)->toDateString();

        $this->putJson(route('vap_samples.samples.update', $entry), $this->payload($entry) + [
            'obs' => 'Corrected observations', 'collected_at' => $date,
            'client_submitted_info' => [
                'lot' => 'CORRECTED-LOT', 'quantity' => '2', 'collected_qty' => '1.5',
                'origin' => 'Corrected origin', 'location' => 'Corrected location',
                'collection_location' => 'Corrected collection site', 'vehicle_reference' => 'CORRECTION-01',
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $entry = $entry->fresh();
        $record = $record->fresh(['collection.collectionable', 'code']);
        $this->assertSame($identity, $this->issuedIdentity($entry));
        $this->assertSame('Corrected observations', $entry->obs);
        $this->assertSame($entry->obs, $record->obs);
        $this->assertSame('CORRECTED-LOT', $record->lot);
        $this->assertSame('2', $record->qty);
        $this->assertSame('1.5', $record->collected_qty);
        $this->assertSame('Corrected location', $record->location);
        $this->assertSame($date, $record->collection_date);
        $this->assertSame($date, $record->collection->collectionable->col_date);
        $this->assertSame([$date], $record->code->analysis()->pluck('col_date')->all());
        $this->assertSame($before['required_parameters'], $entry->client_submitted_info['required_parameters']);
        $this->assertSame($submittedPayload, $record->extra_data->get('submitted_payload'));

        if ($type === 'programmed') {
            $this->assertSame('Corrected collection site', $record->collection->collectionable->collection_location);
            $this->assertSame('CORRECTION-01', $record->collection->collectionable->vehicle_reference);
        }
    }

    #[DataProvider('collectionTypes')]
    public function test_omitted_or_null_nested_metadata_preserves_prior_corrections_and_links(string $type): void
    {
        $entry = $this->intake($type);
        $record = $entry->collectionProduct;
        app(UpdateCollectionAccession::class)->execute($this->lab->id, $record->id, $this->operator->id, $type, [
            'obs' => 'Accession correction', 'lot' => 'ACCESSION-LOT', 'qty' => '7', 'location' => 'Accession location',
        ]);
        $entry = $entry->fresh();
        $this->assertSame('Accession correction', $entry->obs);
        $this->assertSame('ACCESSION-LOT', $entry->client_submitted_info['lot']);
        $this->assertSame('7', $entry->client_submitted_info['quantity']);
        $payload = $entry->client_submitted_info;
        $identity = $this->issuedIdentity($entry);

        foreach ([[], ['client_submitted_info' => null], ['client_submitted_info' => []]] as $changes) {
            $this->putJson(route('vap_samples.samples.update', $entry), array_replace($this->payload($entry), $changes, ['name' => 'Metadata-only title']))
                ->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame('Metadata-only title', $entry->fresh()->name);
            $this->assertSame($payload, $entry->fresh()->client_submitted_info);
            $this->assertSame($identity, $this->issuedIdentity($entry->fresh()));
            $this->assertSame('ACCESSION-LOT', $record->fresh()->lot);
            $this->assertSame('7', $record->fresh()->qty);
        }
    }

    #[DataProvider('identityChanges')]
    public function test_issued_identity_changes_are_rejected_without_any_writes(string $field): void
    {
        $entry = $this->intake('programmed');
        $changes = match ($field) {
            'code' => ['code' => 'REWRITTEN-ISSUED-CODE'],
            'customer_id' => ['customer_id' => Customer::query()->create(['name' => 'Substituted customer'])->id],
            'warehouse_id' => ['warehouse_id' => Warehouse::query()->create(['name' => 'Substituted site', 'customer_id' => $this->customer->id])->id],
            'department_id' => ['department_id' => Department::factory()->create()->id],
            'sample_type' => ['sample_type' => 'OTHER'],
            'requested_services' => ['requested_services' => ['Other service']],
            'portal_request_id' => ['portal_request_id' => CustomerRequest::query()->create(['reference' => 'OTHER-REQUEST', 'title' => 'Other source', 'customer_id' => $this->customer->id, 'warehouse_id' => $this->warehouse->id])->id],
            default => ['client_submitted_info' => match ($field) {
                'product_id' => ['product_id' => Product::query()->create(['name' => 'Substituted product', 'matrix_id' => $this->product->matrix_id])->id],
                'matrix_id' => ['matrix_id' => Matrix::query()->create(['code' => 'OTHER-MATRIX'])->id],
                'requested_profile_ids' => ['requested_profile_ids' => [$this->additionalProfile->id]],
                'collection_type' => ['collection_type' => 'direct'],
                'request_origin' => ['request_origin' => 'internal'],
                'resolved_profile_ids' => ['resolved_profile_ids' => [$this->additionalProfile->id]],
                'required_parameters' => ['required_parameters' => []],
                'linked_lab_code_id' => ['linked_lab_code_id' => 2147483647],
                'linked_sample_ids' => ['linked_sample_ids' => []],
                default => ['linked_collection_type' => 'direct'],
            }],
        };
        $error = array_key_exists('client_submitted_info', $changes) ? 'client_submitted_info.'.$field : $field;
        $snapshot = $this->snapshot();

        $this->putJson(route('vap_samples.samples.update', $entry), array_replace($this->payload($entry), $changes + ['obs' => 'Must not be saved']))
            ->assertUnprocessable()->assertJsonValidationErrors($error);

        $this->assertSame($snapshot, $this->snapshot());
    }

    public function test_catalogue_changes_do_not_recalculate_an_issued_scope_during_metadata_correction(): void
    {
        $entry = $this->intake('direct');
        $scope = $entry->client_submitted_info;
        $this->product->matrix->profiles()->detach();

        $this->putJson(route('vap_samples.samples.update', $entry), $this->payload($entry) + [
            'obs' => 'Catalogue-independent correction', 'client_submitted_info' => ['lot' => 'CATALOGUE-CHANGED'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        foreach (['matrix_id', 'requested_profile_ids', 'resolved_profile_ids', 'resolved_profiles', 'required_parameters'] as $field) {
            $this->assertSame($scope[$field], $entry->fresh()->client_submitted_info[$field]);
        }
        $this->assertSame([$this->profile->id], $entry->collectionProduct->code->analysis()->pluck('profile_id')->all());
    }

    #[DataProvider('collectionTypes')]
    public function test_equivalent_string_identifiers_preserve_the_canonical_issued_snapshot(string $type): void
    {
        $entry = $this->intake($type);
        $identity = $this->issuedIdentity($entry);
        $issuedScope = $entry->client_submitted_info;
        $submittedInfo = array_replace($issuedScope, [
            'product_id' => (string) $issuedScope['product_id'],
            'matrix_id' => (string) $issuedScope['matrix_id'],
            'requested_profile_ids' => array_map(fn (int $id): string => (string) $id, $issuedScope['requested_profile_ids']),
            'lot' => 'STRING-ID-CORRECTION',
        ]);

        $this->putJson(route('vap_samples.samples.update', $entry), $this->payload($entry) + [
            'obs' => 'Same issued identity', 'client_submitted_info' => $submittedInfo,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $entry = $entry->fresh();
        $this->assertSame($identity, $this->issuedIdentity($entry));
        $this->assertSame(array_replace($issuedScope, ['lot' => 'STRING-ID-CORRECTION']), $entry->client_submitted_info);
        $this->assertSame('STRING-ID-CORRECTION', $entry->collectionProduct->lot);
    }

    public function test_edit_response_exposes_issued_identity_and_all_preserved_collection_values(): void
    {
        $entry = $this->intake('direct');
        $entry->update(['collected_by_lab' => true]);

        $this->get(route('vap_samples.index', ['edit' => $entry->id]))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('VAPSamples/Index')
            ->missing('samples')
            ->where('editingSample.id', $entry->id)
            ->where('editingSample.collection_product_id', $entry->collection_product_id)
            ->where('editingSample.collected_by_lab', true)
            ->where('editingSample.collected_at', $entry->collected_at->toISOString())
            ->where('editingSample.received_at', $entry->received_at->toISOString())
            ->where('editingSample.client_submitted_info.linked_lab_code_id', $entry->client_submitted_info['linked_lab_code_id'])
            ->where('editingSample.client_submitted_info.required_parameter_count', $entry->client_submitted_info['required_parameter_count'])
        );
        $this->get(route('vap_samples.index'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('editingSample', null));
    }

    #[DataProvider('collectionTypes')]
    public function test_customer_notes_accept_the_full_intake_limit_without_truncating_custody_evidence(string $type): void
    {
        $notes = str_repeat('á', 2000);
        $entry = $this->intake($type, ['integrity_observations' => $notes]);
        $this->assertSame($notes, $entry->collectionProduct->customer_submitted_info);
        $identity = $this->issuedIdentity($entry);
        $corrected = str_repeat('ç', 2000);

        $this->putJson(route('vap_samples.samples.update', $entry), $this->payload($entry) + [
            'client_submitted_info' => ['customer_submitted_info' => $corrected],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame($corrected, $entry->fresh()->client_submitted_info['customer_submitted_info']);
        $this->assertSame($corrected, $entry->collectionProduct->fresh()->customer_submitted_info);
        $this->assertSame($identity, $this->issuedIdentity($entry->fresh()));

        $this->putJson(route('vap_samples.samples.update', $entry), $this->payload($entry) + [
            'client_submitted_info' => ['customer_submitted_info' => $corrected.'x'],
        ])->assertUnprocessable()->assertJsonValidationErrors('client_submitted_info.customer_submitted_info');
        $this->assertSame($corrected, $entry->collectionProduct->fresh()->customer_submitted_info);
    }

    public function test_unchanged_full_form_values_preserve_custom_retention_and_discard_dates(): void
    {
        $entry = $this->intake('direct');
        $entry->update(['retention_due_at' => now()->addDays(150)->toDateString(), 'discard_scheduled_at' => now()->addDays(180)->toDateString()]);
        $retention = [$entry->retention_due_at->toDateString(), $entry->discard_scheduled_at->toDateString()];

        $this->putJson(route('vap_samples.samples.update', $entry), $this->payload($entry) + [
            'obs' => 'Full form correction', 'received_at' => $entry->received_at->toIso8601String(),
            'retention_period_days' => $entry->retention_period_days, 'client_submitted_info' => $entry->client_submitted_info,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $entry = $entry->fresh();
        $this->assertSame($retention, [$entry->retention_due_at->toDateString(), $entry->discard_scheduled_at->toDateString()]);
    }

    public function test_utc_response_timestamps_round_trip_without_changing_database_wall_times(): void
    {
        $entry = $this->intake('direct');
        $stored = DB::table('sample_entries')->where('id', $entry->id)->first(['received_at', 'collected_at']);

        $this->putJson(route('vap_samples.samples.update', $entry), $this->payload($entry) + [
            'obs' => 'UTC round-trip correction', 'received_at' => $entry->received_at->toISOString(),
            'collected_at' => $entry->collected_at->toISOString(),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertEquals($stored, DB::table('sample_entries')->where('id', $entry->id)->first(['received_at', 'collected_at']));
    }

    public function test_metadata_correction_does_not_reopen_discarded_retention_state(): void
    {
        $entry = $this->intake('direct');
        $entry->update(['retention_status' => 'discarded']);

        $this->putJson(route('vap_samples.samples.update', $entry), $this->payload($entry) + ['obs' => 'Historical metadata correction'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('discarded', $entry->fresh()->retention_status);
    }

    #[DataProvider('protectedEvidence')]
    public function test_ordinary_metadata_edit_cannot_forge_server_decisions_or_audit_evidence(string $field): void
    {
        $entry = $this->intake('direct');
        $snapshot = $this->snapshot();

        $this->putJson(route('vap_samples.samples.update', $entry), $this->payload($entry) + [
            'obs' => 'Must not be saved', 'client_submitted_info' => [$field => ['decision' => 'released', 'user_id' => $this->operator->id]],
        ])->assertUnprocessable()->assertJsonValidationErrors('client_submitted_info.'.$field);

        $this->assertSame($snapshot, $this->snapshot());
    }

    public function test_stored_decision_and_audit_evidence_survive_ordinary_metadata_correction(): void
    {
        $entry = $this->intake('direct');
        $evidence = [
            'qc_release' => ['decision' => 'quarantined', 'decided_by_id' => $this->operator->id],
            'qc_release_history' => [['decision' => 'quarantined']],
            'archived_by' => ['user_id' => $this->operator->id, 'reason' => 'Historical archive'],
        ];
        $entry->update(['client_submitted_info' => array_replace($entry->client_submitted_info, $evidence)]);

        $this->putJson(route('vap_samples.samples.update', $entry), $this->payload($entry) + ['obs' => 'Preserve audit evidence'])
            ->assertRedirect()->assertSessionHasNoErrors();

        foreach ($evidence as $field => $value) {
            $this->assertSame($value, $entry->fresh()->client_submitted_info[$field]);
        }
    }

    #[DataProvider('sourceEvidenceChanges')]
    public function test_issued_portal_row_and_source_evidence_cannot_be_replaced(string $boundary, string $field, mixed $replacement): void
    {
        $request = $this->portalRequest();
        $entry = $this->intake('direct', ['batch_sample_index' => 7], $request);
        $changes = ['obs' => 'Must not be saved', 'client_submitted_info' => [$field => $replacement]];
        $before = $this->snapshot();

        if ($boundary === 'http') {
            $this->putJson(route('vap_samples.samples.update', $entry), $this->payload($entry) + $changes)
                ->assertUnprocessable()->assertJsonValidationErrors('client_submitted_info.'.$field);
        } else {
            try {
                if ($boundary === 'action') {
                    app(UpdateSampleEntry::class)->execute($this->lab->id, $entry->id, $this->operator->id, $changes);
                } else {
                    $entry->update(['obs' => $changes['obs'], 'client_submitted_info' => array_replace($entry->client_submitted_info, $changes['client_submitted_info'])]);
                }

                $this->fail('An issued source row and its original evidence must remain immutable.');
            } catch (ValidationException|LogicException) {
                $this->assertSame($before, $this->snapshot());
            }
        }

        $this->assertSame($before, $this->snapshot());
    }

    public function test_metadata_correction_preserves_original_portal_row_even_when_the_source_is_edited(): void
    {
        $request = $this->portalRequest();
        $entry = $this->intake('programmed', ['batch_sample_index' => 7], $request);
        $originalRow = $entry->client_submitted_info['batch_sample'];
        $identity = $this->issuedIdentity($entry);
        $request->update(['extra_data' => array_replace($request->extra_data->all(), [
            'samples' => [['batch_index' => 7, 'sample_name' => 'Replaced source row', 'lot' => 'NEW-SOURCE-LOT']],
        ])]);

        $this->putJson(route('vap_samples.samples.update', $entry), $this->payload($entry) + [
            'obs' => 'Correct metadata, retain original source',
            'client_submitted_info' => ['lot' => 'CORRECTED-LOT', 'batch_sample_index' => '7'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame($identity, $this->issuedIdentity($entry->fresh()));
        $this->assertSame(7, $entry->fresh()->client_submitted_info['batch_sample_index']);
        $this->assertSame($originalRow, $entry->fresh()->client_submitted_info['batch_sample']);
        $this->assertSame('CORRECTED-LOT', $entry->collectionProduct->fresh()->lot);
        $this->assertSame('Replaced source row', data_get($request->fresh()->extra_data, 'samples.0.sample_name'));
    }

    private function portalRequest(): CustomerRequest
    {
        return CustomerRequest::query()->create([
            'reference' => 'CORRECTION-PORTAL-SOURCE', 'title' => 'Correction source',
            'customer_id' => $this->customer->id, 'warehouse_id' => $this->warehouse->id,
            'extra_data' => [
                'product_id' => $this->product->id, 'matrix_id' => $this->product->matrix_id,
                'requested_profiles' => [$this->profile->id],
                'samples' => [['batch_index' => 7, 'sample_name' => 'Issued portal row', 'lot' => 'ISSUED-SOURCE-LOT']],
            ],
        ]);
    }

    /** @return array<string, array{string, string, mixed}> */
    public static function sourceEvidenceChanges(): array
    {
        $cases = [];

        foreach (['http', 'action', 'model'] as $boundary) {
            foreach (['batch_sample_index' => 3, 'batch_sample' => ['batch_index' => 3, 'sample_name' => 'Other source']] as $field => $replacement) {
                $cases[$boundary.' '.$field] = [$boundary, $field, $replacement];
            }
        }

        return $cases;
    }

    #[DataProvider('collectionTypes')]
    public function test_accession_corrections_reject_duplicate_live_and_archived_same_lab_owners(string $type): void
    {
        $entry = $this->intake($type);
        DB::statement('SET CONSTRAINTS sample_entries_collection_product_unique DEFERRED');
        $other = VAPSampleEntry::factory()->create([
            'lab_id' => $this->lab->id, 'collection_product_id' => $entry->collection_product_id,
            'customer_id' => $entry->customer_id, 'warehouse_id' => $entry->warehouse_id,
        ]);

        foreach ([false, true] as $archived) {
            if ($archived) {
                $other->delete();
            }

            $snapshot = $this->snapshot();

            try {
                app(UpdateCollectionAccession::class)->execute($this->lab->id, $entry->collection_product_id, $this->operator->id, $type, ['lot' => 'Must not be saved']);
                $this->fail('An ambiguous accession cannot be corrected.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('collection_product_id', $exception->errors());
                $this->assertSame($snapshot, $this->snapshot());
            }
        }
    }

    public function test_accession_corrections_reject_a_mismatched_canonical_site(): void
    {
        $entry = $this->intake('direct');
        $site = Warehouse::query()->create(['name' => 'Mismatched canonical site', 'customer_id' => $entry->customer_id]);
        DB::table('sample_entries')->where('id', $entry->id)->update(['warehouse_id' => $site->id]);
        $snapshot = $this->snapshot();

        try {
            app(UpdateCollectionAccession::class)->execute($this->lab->id, $entry->collection_product_id, $this->operator->id, 'direct', ['lot' => 'Must not be saved']);
            $this->fail('Mismatched accession lineage cannot be corrected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('collection_product_id', $exception->errors());
            $this->assertSame($snapshot, $this->snapshot());
        }
    }

    #[DataProvider('modelIdentityChanges')]
    public function test_direct_model_writes_cannot_change_issued_intake_identity(string $field): void
    {
        $entry = $this->intake('direct');
        $data = match ($field) {
            'code' => ['code' => 'REWRITTEN-DIRECTLY'],
            'customer_id' => ['customer_id' => null],
            'warehouse_id' => ['warehouse_id' => null],
            'department_id' => ['department_id' => null],
            'sample_type' => ['sample_type' => 'OTHER'],
            'collection_product_id' => ['collection_product_id' => null],
            'requested_services' => ['requested_services' => ['Other scope']],
            default => ['client_submitted_info' => array_replace($entry->client_submitted_info, [
                $field => match ($field) {
                    'requested_profile_ids', 'resolved_profile_ids', 'linked_sample_ids' => [],
                    'collection_type', 'linked_collection_type' => 'programmed',
                    'request_origin' => 'internal',
                    'analysis_discipline' => 'microbiology',
                    default => 2147483647,
                },
            ])],
        };
        $snapshot = $this->snapshot();

        try {
            $entry->update($data);
            $this->fail('The model must preserve issued identity outside the HTTP action too.');
        } catch (LogicException) {
            $this->assertSame($snapshot, $this->snapshot());
        }
    }

    public function test_a_revoked_membership_is_rechecked_at_the_mutation_boundary(): void
    {
        $entry = $this->intake('direct');
        DB::table('lab_user')->where('user_id', $this->operator->id)->where('lab_id', $this->lab->id)->delete();
        $snapshot = $this->snapshot();

        try {
            app(UpdateSampleEntry::class)->execute($this->lab->id, $entry->id, $this->operator->id, ['obs' => 'Must not be saved']);
            $this->fail('A revoked operator must not mutate an intake.');
        } catch (AuthorizationException) {
            $this->assertSame($snapshot, $this->snapshot());
        }
    }

    public function test_revoked_qualifications_are_not_reused_from_a_cached_user_relation(): void
    {
        $entry = $this->intake('direct');
        $this->operator->load('personnelQualifications');
        $this->qualification->update(['is_active' => false]);
        $snapshot = $this->snapshot();

        try {
            app(UpdateSampleEntry::class)->execute($this->lab->id, $entry->id, $this->operator->id, ['obs' => 'Must not be saved']);
            $this->fail('A revoked qualification must not permit corrections.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            $this->assertSame($snapshot, $this->snapshot());
        }
    }

    public function test_partial_correction_failure_rolls_back_intake_accession_subject_and_analysis(): void
    {
        $entry = $this->intake('programmed');
        $snapshot = $this->snapshot();
        $event = 'eloquent.updating: '.Analysis::class;
        Event::listen($event, fn () => throw new RuntimeException('Injected analytical correction failure'));
        $this->withoutExceptionHandling();

        try {
            $this->put(route('vap_samples.samples.update', $entry), $this->payload($entry) + [
                'obs' => 'Must roll back', 'collected_at' => now()->subDays(7)->toDateString(),
                'client_submitted_info' => ['lot' => 'ROLLBACK-LOT', 'collection_location' => 'Must roll back too'],
            ]);
            $this->fail('The correction should fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected analytical correction failure', $exception->getMessage());
        } finally {
            Event::forget($event);
        }

        $this->assertSame($snapshot, $this->snapshot());
    }

    /** @param array<string, mixed> $additionalInfo */
    private function intake(string $type, array $additionalInfo = [], ?CustomerRequest $request = null): VAPSampleEntry
    {
        $code = fake()->unique()->bothify('CORRECTION-????-######');
        $this->post(route('vap_samples.samples.store'), [
            'name' => 'Issued intake', 'code' => $code, 'sample_type' => 'AGUA', 'lab_id' => $this->lab->id,
            'customer_id' => $this->customer->id, 'warehouse_id' => $this->warehouse->id, 'department_id' => $this->department->id,
            'status' => 'POR_INICIAR', 'received_at' => now()->toDateTimeString(), 'collected_at' => now()->subDay()->toDateTimeString(),
            'portal_request_id' => $request?->id,
            'client_submitted_info' => $additionalInfo + [
                'product_id' => $this->product->id, 'matrix_id' => $this->product->matrix_id,
                'collection_type' => $type, 'request_origin' => 'client', 'requested_profile_ids' => [$this->profile->id],
                'lot' => 'ISSUED-LOT', 'quantity' => '1', 'collection_location' => 'Issued site',
            ],
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('type', 'success');

        return VAPSampleEntry::query()->where('code', $code)->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function payload(VAPSampleEntry $entry): array
    {
        return [
            'name' => $entry->name, 'sample_type' => $entry->sample_type, 'lab_id' => $entry->lab_id,
            'customer_id' => $entry->customer_id, 'warehouse_id' => $entry->warehouse_id, 'department_id' => $entry->department_id,
        ];
    }

    /** @return array<string, mixed> */
    private function issuedIdentity(VAPSampleEntry $entry): array
    {
        return [
            'code' => $entry->code, 'lab_id' => $entry->lab_id, 'customer_id' => $entry->customer_id,
            'warehouse_id' => $entry->warehouse_id, 'department_id' => $entry->department_id,
            'collection_product_id' => $entry->collection_product_id, 'sample_type' => $entry->sample_type,
            'profile_ids' => $entry->client_submitted_info['requested_profile_ids'],
            'product_id' => $entry->client_submitted_info['product_id'], 'matrix_id' => $entry->client_submitted_info['matrix_id'],
            'lab_code_id' => $entry->client_submitted_info['linked_lab_code_id'], 'sample_ids' => $entry->client_submitted_info['linked_sample_ids'],
            'analyses' => $entry->collectionProduct->code->analysis()->get()->map(fn (Analysis $analysis): array => [
                'id' => $analysis->id, 'sample_id' => $analysis->sample_id, 'profile_id' => $analysis->profile_id,
                'cl_id' => $analysis->cl_id, 'department_id' => $analysis->department_id, 'product_id' => $analysis->product_id,
            ])->all(),
        ];
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function snapshot(): array
    {
        $snapshot = [];

        foreach (['sample_entries', 'customer_requests', 'direct_collections', 'programmed_collections', 'collections', 'collection_product', 'lab_codes', 'samples', 'analysis', 'sequence_counters', 'activity_log'] as $table) {
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
    public static function identityChanges(): array
    {
        $fields = ['code', 'customer_id', 'warehouse_id', 'department_id', 'sample_type', 'requested_services', 'portal_request_id',
            'product_id', 'matrix_id', 'requested_profile_ids', 'collection_type', 'request_origin', 'resolved_profile_ids',
            'required_parameters', 'linked_lab_code_id', 'linked_sample_ids', 'linked_collection_type'];

        return array_combine($fields, array_map(fn (string $field): array => [$field], $fields));
    }

    /** @return array<string, array{string}> */
    public static function modelIdentityChanges(): array
    {
        $fields = ['code', 'customer_id', 'warehouse_id', 'department_id', 'sample_type', 'collection_product_id', 'requested_services',
            'product_id', 'matrix_id', 'requested_profile_ids', 'collection_type', 'request_origin', 'analysis_discipline', 'resolved_profile_ids',
            'linked_lab_code_id', 'linked_sample_ids', 'linked_collection_type'];

        return array_combine($fields, array_map(fn (string $field): array => [$field], $fields));
    }

    /** @return array<string, array{string}> */
    public static function protectedEvidence(): array
    {
        $fields = ['qc_release', 'qc_release_history', 'archived_by', 'manual_batch', 'manual_batch_registered_at', 'manual_batch_registered_by_id'];

        return array_combine($fields, array_map(fn (string $field): array => [$field], $fields));
    }
}
