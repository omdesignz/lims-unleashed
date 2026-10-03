<?php

namespace Tests\Feature;

use App\Actions\SetCollectionAccessionArchived;
use App\Actions\UpdateCollectionAccession;
use App\Models\Analysis;
use App\Models\AnalysisCategory;
use App\Models\CollectionCollaboration;
use App\Models\CollectionEndResult;
use App\Models\CollectionProduct;
use App\Models\CollectionReason;
use App\Models\Customer;
use App\Models\Department;
use App\Models\ISOActivityLog;
use App\Models\Matrix;
use App\Models\PackagingCategory;
use App\Models\Parameter;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Profile;
use App\Models\QualityCertificate;
use App\Models\Result;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Support\SampleEntryCollectionFlowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CollectionAccessionWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private User $operator;

    private VAPLab $lab;

    private const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9sX6lz4AAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Notification::fake();
        Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);
        $this->operator = User::factory()->create(['is_active' => true]);
        $this->operator->assignRole(Role::findOrCreate('admin', 'web'));
        $this->lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $this->operator->id]);
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
    }

    public function test_direct_collection_quality_context_is_exposed_and_persisted(): void
    {
        $user = $this->verifiedAdmin();
        $record = $this->collectionProduct('direct', requiresPackaging: true);

        $record->forceFill([
            'sample_status' => 'Recebida',
            'sampling_plan_ref' => 'PLANO-DIRETO-01',
            'customer_submitted_info' => 'Informação inicial',
        ])->save();

        $this->actingAs($user)
            ->get(route('directcollections.edit', ['collection' => $record->id]))
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('DirectCollections/Edit')
                ->where('record.sample_status', 'Recebida')
                ->where('record.sampling_plan_ref', 'PLANO-DIRETO-01')
                ->where('record.customer_submitted_info', 'Informação inicial'));

        $payload = $this->updatePayload($record, [
            'sample_status' => 'Aceite para análise',
            'sampling_plan_ref' => 'PLANO-DIRETO-02',
            'customer_submitted_info' => 'Informação revista',
        ]);

        $this->actingAs($user)
            ->put(route('directcollections.update', ['collection' => $record->id]), $payload)
            ->assertRedirect();

        $record->refresh();

        $this->assertSame('Aceite para análise', $record->sample_status);
        $this->assertSame('PLANO-DIRETO-02', $record->sampling_plan_ref);
        $this->assertSame('Informação revista', $record->customer_submitted_info);
    }

    public function test_programmed_collection_quality_context_is_exposed_and_persisted(): void
    {
        $user = $this->verifiedAdmin();
        $record = $this->collectionProduct('programmed');

        $record->forceFill([
            'sample_status' => 'Programada',
            'sampling_plan_ref' => 'PLANO-PROGRAMADO-01',
            'customer_submitted_info' => 'Condições iniciais',
        ])->save();

        $this->actingAs($user)
            ->get(route('programmedcollections.edit', ['collection' => $record->id]))
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ProgrammedCollections/Edit')
                ->where('record.sample_status', 'Programada')
                ->where('record.sampling_plan_ref', 'PLANO-PROGRAMADO-01')
                ->where('record.customer_submitted_info', 'Condições iniciais'));

        $payload = $this->updatePayload($record, [
            'sample_status' => 'Colhida',
            'sampling_plan_ref' => 'PLANO-PROGRAMADO-02',
            'customer_submitted_info' => 'Condições confirmadas',
            'collection_location' => 'Linha de produção 2',
            'vehicle_reference' => null,
        ]);

        $this->actingAs($user)
            ->put(route('programmedcollections.update', ['collection' => $record->id]), $payload)
            ->assertRedirect();

        $record->refresh();

        $this->assertSame('Colhida', $record->sample_status);
        $this->assertSame('PLANO-PROGRAMADO-02', $record->sampling_plan_ref);
        $this->assertSame('Condições confirmadas', $record->customer_submitted_info);
    }

    #[DataProvider('collectionTypes')]
    public function test_customer_notes_share_the_intake_limit_and_never_truncate_values(string $type): void
    {
        $record = $this->collectionProduct($type);
        $notes = str_repeat('á', 2000);
        $route = route($type.'collections.update', ['collection' => $record->id]);

        $this->putJson($route, ['customer_submitted_info' => $notes])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($notes, $record->fresh()->customer_submitted_info);
        $this->assertSame($notes, $record->sampleEntry->fresh()->client_submitted_info['customer_submitted_info']);

        $this->putJson($route, ['customer_submitted_info' => $notes.'x'])->assertUnprocessable()->assertJsonValidationErrors('customer_submitted_info');
        $this->assertSame($notes, $record->fresh()->customer_submitted_info);
    }

    #[DataProvider('collectionTypes')]
    public function test_scalar_and_selector_inputs_share_flat_validation_and_preserve_unsent_fields(string $type): void
    {
        $record = $this->collectionProduct($type, requiresPackaging: true);
        $collaboration = CollectionCollaboration::query()->create(['name' => 'Accession collaborator']);
        $reason = CollectionReason::query()->create(['name' => 'Accession reason']);
        $payload = $this->updatePayload($record, [
            'collaborations' => [$collaboration->id],
            'collectionreasons' => [['reason_id' => $reason->id]],
            'owner_id' => $this->operator->id,
            'obs' => 'Contexto preservado',
            'qty' => 0,
            'collected_qty' => 0,
        ]);
        foreach (['customer_id', 'warehouse_id', 'product_id', 'result_id', 'pack_id'] as $field) {
            $payload[$field] = $record->{$field};
        }

        $this->put(route($type.'collections.update', $record->id), $payload)
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Contexto preservado', $record->fresh()->obs);
        $this->assertSame($this->operator->id, $record->fresh()->owner_id);
        $this->assertSame([$collaboration->id], $record->collection->collaborations()->pluck('collection_collaborations.id')->all());
        $this->assertSame([$reason->id], $record->collection->reasons()->pluck('collection_reasons.id')->all());
        $this->assertEquals(0, $record->fresh()->qty);

        unset($payload['obs'], $payload['collaborations'], $payload['collectionreasons']);
        $this->put(route($type.'collections.update', $record->id), $payload)
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Contexto preservado', $record->fresh()->obs);
        $this->assertSame([$collaboration->id], $record->collection->collaborations()->pluck('collection_collaborations.id')->all());

        $payload['product_id'] = null;
        $this->putJson(route($type.'collections.update', $record->id), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('product_id')
            ->assertJsonMissingValidationErrors('products.0.product_id');
    }

    #[DataProvider('immutableIdentityFields')]
    public function test_corrections_cannot_reassign_intake_identity(string $type, string $field): void
    {
        $record = $this->collectionProduct($type, requiresPackaging: true);
        $other = $this->collectionProduct($type, requiresPackaging: true, lab: VAPLab::factory()->create());
        $before = $record->getAttributes();
        $value = $field === 'invoice_id' ? 999999999 : $other->{$field};

        $this->putJson(route($type.'collections.update', $record->id), $this->updatePayload($record, [
            $field => $value, 'obs' => 'Must not be written',
        ]))->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertSame($before, $record->fresh()->getAttributes());
    }

    #[DataProvider('collectionTypes')]
    public function test_canonical_intake_accepts_metadata_corrections_without_legacy_required_selections(string $type): void
    {
        $record = $this->collectionProduct($type);
        $record->update(['result_id' => null, 'pack_id' => null, 'collected_qty' => null]);

        $this->putJson(route($type.'collections.update', $record->id), [
            'sampling_plan_ref' => 'Corrected plan', 'obs' => 'Correction after intake',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Corrected plan', $record->fresh()->sampling_plan_ref);
        $this->assertSame('Correction after intake', $record->fresh()->obs);
        $this->assertNull($record->fresh()->result_id);
        $this->assertNull($record->fresh()->pack_id);
        $this->assertNull($record->fresh()->collected_qty);
        $this->assertEquals(1, $record->fresh()->qty);
    }

    #[DataProvider('collectionTypes')]
    public function test_lists_search_reads_and_documents_are_lab_and_type_scoped(string $type): void
    {
        $local = $this->collectionProduct($type, requiresPackaging: true);
        $peer = $this->collectionProduct($type, requiresPackaging: true, lab: VAPLab::factory()->create());
        $wrongType = $this->collectionProduct($type === 'direct' ? 'programmed' : 'direct', requiresPackaging: true);
        $unowned = $this->collectionProduct($type, requiresPackaging: true);
        $unowned->sampleEntry->delete();
        $conflict = $this->collectionProduct($type, requiresPackaging: true);
        DB::statement('SET CONSTRAINTS sample_entries_collection_product_unique DEFERRED');
        VAPSampleEntry::factory()->create([
            'lab_id' => VAPLab::factory()->create()->id, 'customer_id' => $conflict->customer_id,
            'collection_product_id' => $conflict->id, 'deleted_at' => now(),
        ]);
        $malformed = $this->collectionProduct($type, requiresPackaging: true);
        $malformed->collection->update(['customer_id' => $peer->customer_id]);
        $deletedSubject = $this->collectionProduct($type, requiresPackaging: true);
        $deletedSubject->collection->collectionable->delete();

        $this->get(route($type.'collections.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('record.meta.total', 1)->where('record.data.0.id', $local->id));
        if ($type === 'direct') {
            $this->get(route('directcollections.index'))->assertInertia(fn (Assert $page) => $page
                ->where('stats.0.value', 1)->where('stats.2.value', 1));
        } else {
            foreach ([$peer->customer->name, $wrongType->code->code, $conflict->warehouse->address] as $search) {
                $this->get(route('programmedcollections.index', ['search' => $search]))->assertOk()
                    ->assertInertia(fn (Assert $page) => $page->where('record.meta.total', 0));
            }
        }

        foreach ([$peer, $wrongType, $unowned, $conflict, $malformed, $deletedSubject] as $blocked) {
            foreach (['show', 'edit'] as $operation) {
                $this->get(route($type.'collections.'.$operation, $blocked->id))->assertNotFound();
            }
            foreach (['getCollectionLabels', 'getCollectionTermPDF', 'getParametersToAnalyzePDF'] as $operation) {
                $this->get(route($type.'collections.'.$operation, ['id' => $blocked->id]))->assertNotFound();
            }
            $this->putJson(route($type.'collections.update', $blocked->id), $this->updatePayload($blocked))
                ->assertNotFound();
        }
    }

    #[DataProvider('collectionTypes')]
    public function test_show_requires_view_permission_and_owner_options_require_local_membership(string $type): void
    {
        $record = $this->collectionProduct($type, requiresPackaging: true);
        $editor = User::factory()->create(['is_active' => true]);
        $editor->givePermissionTo(Permission::findOrCreate('edit_'.$type.'_collections', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $editor->id]);
        $foreign = User::factory()->create(['is_active' => true]);

        $this->actingAs($editor)->get(route($type.'collections.show', $record->id))->assertForbidden();
        $this->get(route($type.'collections.edit', $record->id))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('record.sample_entry_url', route('vap_samples.show', $record->sampleEntry->id))
                ->where('ownerOptions', fn ($options): bool => collect($options)->pluck('value')->sort()->values()->all()
                    === collect([$this->operator->id, $editor->id])->sort()->values()->all())
                ->missing('record.invoice_id'));
        $this->putJson(route($type.'collections.update', $record->id), $this->updatePayload($record, ['owner_id' => $foreign->id]))
            ->assertUnprocessable()->assertJsonValidationErrors('owner_id');
        $this->assertNull($record->fresh()->owner_id);
    }

    #[DataProvider('collectionTypes')]
    public function test_correction_does_not_accept_server_managed_workflow_flags(string $type): void
    {
        $record = $this->collectionProduct($type, requiresPackaging: true);
        $before = $record->only(['processed', 'invoiced', 'status']);

        $this->put(route($type.'collections.update', $record->id), $this->updatePayload($record, [
            'processed' => ! $record->processed, 'invoiced' => ! $record->invoiced, 'status' => ! $record->status,
            'obs' => 'Valid correction',
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame($before, $record->fresh()->only(['processed', 'invoiced', 'status']));
        $this->assertSame('Valid correction', $record->fresh()->obs);
    }

    public function test_correction_rolls_back_product_parent_and_analytical_dates_on_failure(): void
    {
        $record = $this->collectionProduct('programmed', requiresPackaging: true);
        $analysis = $record->code->analysis()->sole();
        $productBefore = $record->getAttributes();
        $subjectBefore = $record->collection->collectionable->getAttributes();
        $analysisBefore = $analysis->fresh()->getAttributes();
        Analysis::updating(fn () => throw new RuntimeException('Simulated analytical date failure'));
        $this->withoutExceptionHandling();

        try {
            $this->put(route('programmedcollections.update', $record->id), $this->updatePayload($record, [
                'obs' => 'Must roll back', 'collection_date' => now()->addDay()->toDateString(),
                'collection_location' => 'Must roll back too',
            ]));
            $this->fail('Expected the injected persistence failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated analytical date failure', $exception->getMessage());
        }

        $this->assertSame($productBefore, $record->fresh()->getAttributes());
        $this->assertSame($subjectBefore, $record->collection->collectionable->fresh()->getAttributes());
        $this->assertSame($analysisBefore, $analysis->fresh()->getAttributes());
    }

    #[DataProvider('collectionTypes')]
    public function test_archive_and_restore_preserve_graph_identifiers_results_certificates_and_signature_files(string $type): void
    {
        Storage::fake('public');
        config(['media-library.disk_name' => 'public']);
        $record = $this->collectionProduct($type, requiresPackaging: true);
        $entry = $record->sampleEntry;
        $code = $record->code;
        $analysis = $code->analysis()->sole();
        $parameter = Parameter::query()->create(['name' => 'Preserved archival parameter']);
        $result = Result::query()->create([
            'sample_id' => $analysis->sample_id, 'profile_id' => $analysis->profile_id, 'parameter_id' => $parameter->id,
            'code_id' => $code->id, 'collection_id' => $record->id, 'product_id' => $record->product_id,
            'approved_value' => '0', 'approval_notes' => 'Preserve exact evidence',
        ]);
        $signature = $result->addMediaFromBase64(self::SIGNATURE)->usingFileName('preserved.png')->toMediaCollection('approval_signature');
        $certificate = QualityCertificate::query()->create([
            'collection_id' => $record->id, 'cl_id' => $code->id, 'customer_id' => $record->customer_id,
            'warehouse_id' => $record->warehouse_id, 'product_id' => $record->product_id, 'user_id' => $this->operator->id,
            'code' => 'Archive certificate',
        ]);
        $graphBefore = [$analysis->fresh()->getAttributes(), $result->fresh()->getAttributes(), $certificate->fresh()->getAttributes(), $entry->code, $code->code];

        $this->getJson(route($type.'collections.destroy', ['recordIds' => [$record->id]]))->assertStatus(405);
        $this->getJson(route($type.'collections.restore', ['recordIds' => [$record->id]]))->assertStatus(405);
        $this->post(route($type.'collections.destroy'), ['recordIds' => [$record->id]])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertTrue($record->fresh()->trashed());
        $this->assertTrue($entry->fresh()->trashed());
        $this->assertFalse($record->collection->fresh()->trashed());
        $this->assertFalse($record->collection->collectionable->fresh()->trashed());
        $this->assertFalse($analysis->fresh()->trashed());
        $this->assertFalse($certificate->fresh()->trashed());
        $this->assertSame($graphBefore, [$analysis->fresh()->getAttributes(), $result->fresh()->getAttributes(), $certificate->fresh()->getAttributes(), $entry->fresh()->code, $code->fresh()->code]);
        $this->assertSame($signature->id, $result->fresh()->getFirstMedia('approval_signature')?->id);
        $this->assertFileExists($signature->getPath());
        $activityCount = DB::table('activity_log')->count();
        $this->post(route($type.'collections.destroy'), ['recordIds' => [$record->id]])->assertRedirect();
        $this->assertSame($activityCount, DB::table('activity_log')->count());
        $this->get(route($type.'collections.show', $record->id))->assertNotFound();
        $collectionCount = CollectionProduct::withTrashed()->count();
        $existing = app(SampleEntryCollectionFlowService::class)->sync($entry->fresh());
        $this->assertSame($record->id, $existing->id);
        $this->assertTrue($existing->trashed());
        $this->assertSame($collectionCount, CollectionProduct::withTrashed()->count());

        $filters = $type === 'direct' ? ['filter' => ['trashed' => 'only']] : ['filter' => 'trashed'];
        $this->get(route($type.'collections.index', ['category' => 'archived'] + $filters))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('record.meta.total', 1)->where('record.data.0.id', $record->id)
                ->where('record.data.0.deleted', true)->where('record.data.0.sample_entry.id', $entry->id));

        $this->post(route($type.'collections.restore'), ['recordIds' => [$record->id]])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($record->fresh()->trashed());
        $this->assertFalse($entry->fresh()->trashed());
        $this->get(route($type.'collections.show', $record->id))->assertOk();
        $this->assertSame($graphBefore, [$analysis->fresh()->getAttributes(), $result->fresh()->getAttributes(), $certificate->fresh()->getAttributes(), $entry->fresh()->code, $code->fresh()->code]);
        $this->assertSame($signature->id, $result->fresh()->getFirstMedia('approval_signature')?->id);
        $this->assertFileExists($signature->getPath());
        $activityCount = DB::table('activity_log')->count();
        $this->post(route($type.'collections.restore'), ['recordIds' => [$record->id]])->assertRedirect();
        $this->assertSame($activityCount, DB::table('activity_log')->count());
    }

    #[DataProvider('collectionTypes')]
    public function test_bulk_archive_and_restore_reject_the_entire_mixed_lab_batch(string $type): void
    {
        $local = $this->collectionProduct($type, requiresPackaging: true);
        $foreign = $this->collectionProduct($type, requiresPackaging: true, lab: VAPLab::factory()->create());
        $payload = ['recordIds' => [$local->id, $foreign->id]];
        $this->postJson(route($type.'collections.destroy'), $payload)->assertNotFound();
        $this->assertFalse($local->fresh()->trashed());
        $this->assertFalse($local->sampleEntry->fresh()->trashed());
        $this->assertFalse($foreign->fresh()->trashed());
        $this->post(route($type.'collections.destroy'), ['recordIds' => [$local->id]])->assertRedirect();
        $this->postJson(route($type.'collections.restore'), $payload)->assertNotFound();
        $this->assertTrue($local->fresh()->trashed());
        $this->assertFalse($foreign->fresh()->trashed());
        $this->postJson(route($type.'collections.destroy'), ['recordIds' => [$foreign->id]])->assertNotFound();
    }

    #[DataProvider('collectionTypes')]
    public function test_parameter_sheet_and_labels_include_only_the_owned_collection_type(string $type): void
    {
        $local = $this->collectionProduct($type, requiresPackaging: true);
        $peer = $this->collectionProduct($type, requiresPackaging: true, lab: VAPLab::factory()->create());
        $otherType = $this->collectionProduct($type === 'direct' ? 'programmed' : 'direct', requiresPackaging: true);
        $parameter = Parameter::query()->create(['name' => 'Shared parameter for scoped sheets']);
        foreach ([$local, $peer, $otherType] as $record) {
            $record->code->analysis()->sole()->profile->parameters()->attach($parameter->id);
        }

        $this->get(route($type.'collections.getMultipleParametersToAnalyzePDF', ['recordIds' => [$parameter->id]]))
            ->assertOk()->assertViewHas('models', fn ($models): bool => $models->count() === 1
                && $models->first()['code'] === $local->code->code);
        $this->get(route($type.'collections.getCollectionLabels', ['id' => $local->id]))
            ->assertOk()->assertViewHas('model', fn ($code): bool => $code->id === $local->code->id);
    }

    public function test_a_bulk_archive_failure_rolls_back_both_intake_roots_and_activity(): void
    {
        $first = $this->collectionProduct('direct', requiresPackaging: true);
        $second = $this->collectionProduct('direct', requiresPackaging: true);
        $firstEntry = $first->sampleEntry;
        $secondEntry = $second->sampleEntry;
        $activityCount = DB::table('activity_log')->count();
        $activityWrites = 0;
        ISOActivityLog::creating(function () use (&$activityWrites): void {
            if (++$activityWrites === 2) {
                throw new RuntimeException('Simulated second archive failure');
            }
        });

        try {
            app(SetCollectionAccessionArchived::class)->execute($this->lab->id, $this->operator->id, 'direct', [$first->id, $second->id], archived: true);
            $this->fail('Expected the second archive failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated second archive failure', $exception->getMessage());
        }

        foreach ([$first, $second, $firstEntry, $secondEntry] as $record) {
            $this->assertFalse($record->fresh()->trashed());
        }
        $this->assertSame($activityCount, DB::table('activity_log')->count());
    }

    public function test_actions_recheck_current_permission_before_writing(): void
    {
        $record = $this->collectionProduct('direct', requiresPackaging: true);
        $editor = User::factory()->create(['is_active' => true]);
        $permission = Permission::findOrCreate('edit_direct_collections', 'web');
        $editor->givePermissionTo($permission);
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $editor->id]);
        $update = app(UpdateCollectionAccession::class);
        $editor->revokePermissionTo($permission);

        try {
            $update->execute($this->lab->id, $record->id, $editor->id, 'direct', ['obs' => 'Unauthorized']);
            $this->fail('A revoked editor may not write corrections.');
        } catch (AuthorizationException) {
            $this->assertNull($record->fresh()->obs);
        }

        try {
            app(SetCollectionAccessionArchived::class)->execute($this->lab->id, $editor->id, 'direct', [$record->id], archived: true);
            $this->fail('An editor without archive permission may not archive the graph.');
        } catch (AuthorizationException) {
            $this->assertFalse($record->fresh()->trashed());
            $this->assertFalse($record->sampleEntry->fresh()->trashed());
        }
    }

    /** @return array<string, array{string}> */
    public static function collectionTypes(): array
    {
        return ['direct' => ['direct'], 'programmed' => ['programmed']];
    }

    /** @return array<string, array{string, string}> */
    public static function immutableIdentityFields(): array
    {
        $cases = [];
        foreach (['direct', 'programmed'] as $type) {
            foreach (['customer_id', 'warehouse_id', 'product_id', 'collection_id', 'invoice_id'] as $field) {
                $cases[$type.' '.$field] = [$type, $field];
            }
        }

        return $cases;
    }

    private function verifiedAdmin(): User
    {
        return $this->operator;
    }

    private function collectionProduct(string $type, bool $requiresPackaging = false, ?VAPLab $lab = null): CollectionProduct
    {
        $customer = Customer::query()->create(['name' => fake()->unique()->company()]);
        $warehouse = Warehouse::query()->create([
            'name' => fake()->unique()->bothify('Accession site ######'),
            'address' => fake()->address(), 'customer_id' => $customer->id,
        ]);
        $department = Department::factory()->create();
        $category = AnalysisCategory::query()->create([
            'name' => fake()->unique()->bothify('Accession category ######'),
            'code' => fake()->unique()->bothify('AC-######'), 'department_id' => $department->id,
        ]);
        $matrix = Matrix::query()->create(['code' => fake()->unique()->bothify('AC-M-######')]);
        $profile = Profile::query()->create([
            'name' => fake()->unique()->bothify('Accession profile ######'),
            'code' => fake()->unique()->bothify('AC-P-######'), 'category_id' => $category->id,
        ]);
        $matrix->profiles()->attach($profile->id);
        $product = Product::query()->create(['name' => 'Accession product', 'matrix_id' => $matrix->id]);
        $packaging = $requiresPackaging ? PackagingCategory::query()->create(['name' => fake()->unique()->bothify('Accession packaging ######')]) : null;
        $entry = VAPSampleEntry::factory()->create([
            'lab_id' => ($lab ?? $this->lab)->id, 'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id,
            'department_id' => $department->id, 'received_by_id' => $this->operator->id,
            'received_at' => now(), 'packaging_id' => $packaging?->id,
            'client_submitted_info' => ['product_id' => $product->id, 'collection_type' => $type, 'quantity' => 1, 'collected_qty' => 1],
        ]);
        $record = DB::transaction(fn () => app(SampleEntryCollectionFlowService::class)->sync($entry));
        $record->update(['result_id' => CollectionEndResult::query()->create(['name' => fake()->unique()->bothify('Accession accepted ######')])->id]);

        return $record->fresh(['collection.collaborations', 'collection.reasons']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function updatePayload(CollectionProduct $record, array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $this->option($record->customer_id),
            'warehouse_id' => $this->option($record->warehouse_id),
            'collaborations' => $record->collection->collaborations
                ->map(fn ($collaboration): array => $this->option($collaboration->id, $collaboration->name))
                ->values()
                ->all(),
            'collectionreasons' => $record->collection->reasons
                ->map(fn ($reason): array => $this->option($reason->id, $reason->name))
                ->values()
                ->all(),
            'collection_date' => now()->toDateString(),
            'collection_id' => $record->collection_id,
            'product_id' => $this->option($record->product_id),
            'temperature_id' => $this->nullableOption($record->temperature_id),
            'vehicle_id' => $this->nullableOption($record->vehicle_id),
            'owner_id' => $this->nullableOption($record->owner_id),
            'result_id' => $this->option($record->result_id),
            'pack_id' => $this->nullableOption($record->pack_id),
            'invoice_id' => $this->nullableOption($record->invoice_id),
            'comercial_brand' => $record->comercial_brand,
            'du_no' => $record->du_no,
            'origin' => $record->origin,
            'location' => $record->location,
            'term_no' => $record->term_no,
            'container_no' => $record->container_no,
            'recollection' => (bool) $record->recollection,
            'obs' => $record->obs,
            'sample_status' => $record->sample_status,
            'sampling_plan_ref' => $record->sampling_plan_ref,
            'customer_submitted_info' => $record->customer_submitted_info,
            'processed' => (bool) $record->processed,
            'collected_by_lab' => (bool) $record->collected_by_lab,
            'expiry_date' => $this->dateValue($record->expiry_date),
            'production_date' => $this->dateValue($record->production_date),
            'qty' => $record->qty ?: '1',
            'collected_qty' => $record->collected_qty ?: '1',
            'lot' => $record->lot,
            'bl' => $record->bl,
            'temperature_value' => $record->temperature_value,
            'invoiced' => (bool) $record->invoiced,
            'status' => (bool) $record->status,
        ], $overrides);
    }

    /**
     * @return array{value: int, label: string}
     */
    private function option(int $value, ?string $label = null): array
    {
        return [
            'value' => $value,
            'label' => $label ?? (string) $value,
        ];
    }

    /**
     * @return array{value: int, label: string}|null
     */
    private function nullableOption(?int $value): ?array
    {
        return $value ? $this->option($value) : null;
    }

    private function dateValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr((string) $value, 0, 10);
    }
}
