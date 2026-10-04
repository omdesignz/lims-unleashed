<?php

namespace Tests\Feature;

use App\Actions\ValidateQualityCertificate;
use App\Models\Analysis;
use App\Models\AnalysisCategory;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Matrix;
use App\Models\NormativeWorkProcedure;
use App\Models\Parameter;
use App\Models\PersonnelQualification;
use App\Models\Product;
use App\Models\Profile;
use App\Models\Protocol;
use App\Models\QualityCertificate;
use App\Models\Result;
use App\Models\ResultCategory;
use App\Models\Standard;
use App\Models\Unit;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Support\AnalysisReportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Phase 3 exit journey: a shared customer, a private proposal in laboratory A, its
 * public acceptance, sample intake, three-person result review and a controlled
 * certificate, while laboratory B never sees or changes any of it.
 */
class LaboratoryJourneyEndToEndTest extends TestCase
{
    use DatabaseTransactions;

    private const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9sX6lz4AAAAASUVORK5CYII=';

    private VAPLab $lab;

    private VAPLab $peerLab;

    private Department $department;

    private Customer $customer;

    private Warehouse $site;

    private Product $product;

    private Profile $profile;

    /** @var array<string, User> */
    private array $people = [];

    private User $peer;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        config(['broadcasting.default' => 'null', 'mail.default' => 'array']);
        Storage::fake('public');
        Storage::fake('local');
        Storage::fake(config('filesystems.default', 'local'));
        config(['media-library.disk_name' => 'public']);
        Notification::fake();
        Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);

        $this->lab = VAPLab::factory()->create(['name' => 'Laboratório Central E2E']);
        $this->peerLab = VAPLab::factory()->create(['name' => 'Laboratório Norte E2E']);
        $this->department = Department::factory()->create();
        $this->customer = Customer::query()->create(['name' => 'Cliente partilhado E2E']);
        $this->site = Warehouse::query()->create(['name' => 'Fábrica E2E', 'address' => 'Viana, Luanda', 'customer_id' => $this->customer->id]);
        $this->catalogue();

        $roles = [
            'commercial' => ['add_proposals', 'edit_proposals', 'view_proposals'],
            'reception' => ['add_samples', 'view_samples', 'view_proposals'],
            'analyst' => ['insert_results', 'view_results', 'view_analysis'],
            'verifier' => ['verify_results', 'view_results', 'view_analysis'],
            'approver' => ['approve_results', 'view_results', 'view_analysis'],
            'releaser' => ['view_quality_certificates', 'validate_quality_certificates', 'edit_quality_certificates', 'view_proposals'],
            'customers' => ['view_customers'],
        ];
        $capabilities = [
            'reception' => 'sample_intake_validation', 'analyst' => 'insert_results',
            'verifier' => 'verify_results', 'approver' => 'approve_results',
        ];
        foreach ($roles as $role => $permissions) {
            $user = User::factory()->create(['is_active' => true, 'name' => 'E2E '.$role]);
            $user->givePermissionTo(array_map(fn (string $name) => Permission::findOrCreate($name, 'web'), $permissions));
            DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $user->id]);
            if (isset($capabilities[$role])) {
                $this->qualify($user, $capabilities[$role]);
            }
            $this->people[$role] = $user;
        }

        $this->peer = User::factory()->create(['is_active' => true, 'name' => 'E2E peer']);
        $this->peer->givePermissionTo(array_map(fn (string $name) => Permission::findOrCreate($name, 'web'), array_merge(...array_values($roles))));
        DB::table('lab_user')->insert(['lab_id' => $this->peerLab->id, 'user_id' => $this->peer->id]);
    }

    public function test_two_laboratory_journey_from_private_proposal_to_controlled_certificate(): void
    {
        // 1. Laboratory A authors a private proposal for the shared customer.
        $template = VAPProposalTemplate::query()->create(['name' => 'Modelo E2E', 'content' => '<p>Condições</p>', 'user_id' => $this->people['commercial']->id, 'is_active' => true]);
        $unit = Unit::query()->create(['code' => 'E2E-'.Str::random(6), 'description' => 'Ensaio']);
        $this->as('commercial')->post(route('vap-proposals.store'), [
            'customer_id' => $this->customer->id, 'warehouse_id' => $this->site->id, 'department_id' => $this->department->id,
            'template_id' => $template->id, 'service_location' => 'Fábrica de Viana', 'tolerance_days' => 15,
            'items' => [['item_description' => 'Água de processo — físico-química', 'unit_id' => $unit->id, 'qty' => 2, 'unit_price' => 15000]],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $proposal = VAPProposal::withoutGlobalScopes()->where('service_location', 'Fábrica de Viana')->sole();
        $this->assertSame($this->lab->id, $proposal->lab_id);

        // Laboratory B shares the customer but never the proposal.
        // The 404 comes from route-model binding, before the Inertia middleware: the error page still needs its shared routes.
        Inertia::flushShared();
        $this->asPeer()->get(route('vap-proposals.show', $proposal))->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 404)->has('ziggy.routes')->has('auth'));

        $this->assertSame('30000.00', (string) $proposal->sub_total);
        $this->as('commercial')->get(route('vap-proposals.index', ['search' => 'cliente partilhado']))
            ->assertInertia(fn (Assert $page) => $page->where('proposals.data.0.id', $proposal->id));
        $this->asPeer()->get(route('vap-proposals.index', ['search' => 'cliente partilhado']))
            ->assertInertia(fn (Assert $page) => $page->has('proposals.data', 0));

        // A revision recalculates totals on the server and keeps the previous version in history.
        $revision = ['customer_id' => $this->customer->id, 'warehouse_id' => $this->site->id, 'department_id' => $this->department->id,
            'template_id' => $template->id, 'service_location' => 'Fábrica de Viana', 'tolerance_days' => 15, 'revision_reason' => 'Cliente pediu mais uma amostra.',
            'items' => [['item_description' => 'Água de processo — físico-química', 'unit_id' => $unit->id, 'qty' => 3, 'unit_price' => 15000, 'sub_total' => 1, 'total' => 1]]];
        $this->as('commercial')->put(route('vap-proposals.update', $proposal), $revision)->assertRedirect()->assertSessionHasNoErrors();
        $proposal->refresh();
        $this->assertSame(['REVISED', '45000.00'], [$proposal->status, (string) $proposal->sub_total]);
        $history = DB::table('activity_log')->where('subject_type', $proposal->getMorphClass())->where('subject_id', $proposal->id)->where('event', 'revised')->sole();
        $this->assertSame('30.000,00', number_format((float) data_get(json_decode($history->properties, true), 'old_values.sub_total'), 2, ',', '.'));
        $this->as('commercial')->put(route('vap-proposals.update', $proposal), $revision)->assertStatus(409);
        $this->asPeer()->put(route('vap-proposals.update', $proposal), $revision)->assertNotFound();

        // 2. Sent, then accepted by the customer through the public link.
        $this->as('commercial')->post(route('vap-proposals.send', $proposal))->assertRedirect();
        $this->assertSame('SENT', $proposal->fresh()->status);
        $this->postJson(route('proposals.api.accept', $proposal->unique_hash), ['confidentiality' => true, 'impartiality' => true, 'nondisclosure' => true])
            ->assertOk();
        $this->postJson(route('proposals.api.accept', $proposal->unique_hash), ['confidentiality' => true, 'impartiality' => true, 'nondisclosure' => true])
            ->assertStatus(400);
        $this->assertSame('ACCEPTED', $proposal->fresh()->status);
        $this->assertSame('awaiting_samples', $this->dossierStage($proposal));

        // 3. Reception registers the sample from the accepted proposal; accession and analysis follow.
        $this->as('reception')->get(route('vap_samples.index', ['proposal_id' => $proposal->id, 'start' => 1]))
            ->assertInertia(fn (Assert $page) => $page->where('entryWorkflowDefaults.proposal.id', $proposal->id));
        $this->as('reception')->post(route('vap_samples.samples.store'), [
            'name' => 'Água de processo — linha 2', 'sample_type' => 'AGUA', 'status' => 'POR_INICIAR',
            'proposal_id' => $proposal->id, 'customer_id' => $this->customer->id, 'warehouse_id' => $this->site->id,
            'department_id' => $this->department->id, 'lab_id' => $this->lab->id,
            'received_at' => now()->toDateTimeString(), 'collected_at' => now()->subHour()->toDateTimeString(),
            'client_submitted_info' => [
                'request_origin' => 'client', 'collection_type' => 'direct',
                'product_id' => $this->product->id, 'matrix_id' => $this->product->matrix_id,
                'requested_profile_ids' => [$this->profile->id],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $entry = VAPSampleEntry::query()->where('proposal_id', $proposal->id)->sole();
        $this->assertSame($this->lab->id, $entry->lab_id);
        $this->assertNotNull($entry->collection_product_id);
        $analysis = Analysis::query()->whereIn('sample_id', data_get($entry->client_submitted_info, 'linked_sample_ids', []))->sole();
        $this->assertSame('results', $this->dossierStage($proposal));
        $this->asPeer()->get(route('vap_samples.show', $entry))->assertNotFound();
        $this->asPeer()->get(route('vap_samples.index', ['edit' => $entry->id]))->assertNotFound();
        $this->asPeer()->get(route('vap_samples.index', ['discard' => $entry->id]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('discardableSamples', fn ($samples) => collect($samples)->doesntContain('id', $entry->id)));
        $this->asPeer()->get(route('customers.show', $this->customer))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('customerState.summary.accepted_proposals', 0)
                ->where('customerState.summary.samples_in_progress', 0)
                ->has('customerState.recent_samples', 0));

        // 4. Results: three different people insert, verify and approve.
        $rows = $this->resultRows('analyst', $analysis, 'analyze', 'inserted_value', '7.2');
        $this->as('analyst')->post(route('results.store'), ['action' => 'analyze', 'sample_id' => $analysis->sample_id, 'results' => $rows])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('verification', $this->dossierStage($proposal));

        // The analyst also holds a verification qualification, but four-eyes still refuses.
        $this->people['analyst']->givePermissionTo(Permission::findOrCreate('verify_results', 'web'));
        $this->qualify($this->people['analyst'], 'verify_results');
        $rows = $this->resultRows('analyst', $analysis, 'verify', 'verified_value', '7.2');
        $this->as('analyst')->post(route('results.store'), ['action' => 'verify', 'sample_id' => $analysis->sample_id, 'results' => $rows, 'signature' => self::SIGNATURE])
            ->assertSessionHasErrors('results');
        $this->assertSame(0, Result::query()->where('sample_id', $analysis->sample_id)->whereNotNull('verified_date')->count());

        $rows = $this->resultRows('verifier', $analysis, 'verify', 'verified_value', '7.2');
        $this->as('verifier')->post(route('results.store'), ['action' => 'verify', 'sample_id' => $analysis->sample_id, 'results' => $rows, 'signature' => self::SIGNATURE])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('approval', $this->dossierStage($proposal));

        $rows = $this->resultRows('approver', $analysis, 'approve', 'approved_value', '7.2');
        $this->as('approver')->post(route('results.store'), ['action' => 'approve', 'sample_id' => $analysis->sample_id, 'results' => $rows, 'signature' => self::SIGNATURE])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('report_generation', $this->dossierStage($proposal));

        // 5. The certificate is generated once, even when the request is repeated.
        $this->as('releaser')->post(route('laboratory-workflow.reports.store', $proposal))->assertRedirect();
        $this->as('releaser')->post(route('laboratory-workflow.reports.store', $proposal))->assertRedirect();
        $certificate = QualityCertificate::withoutGlobalScopes()->where('collection_id', $entry->collection_product_id)->sole();
        $this->assertSame('report_validation', $this->dossierStage($proposal));

        // Laboratory B can neither list, open, download nor validate it.
        $this->asPeer()->get(route('qualitycertificates.show', $certificate))->assertNotFound();
        $this->asPeer()->get(route('qualitycertificates.getPDF', ['id' => $certificate->id]))->assertNotFound();
        $this->asPeer()->post(route('qualitycertificates.approve', $certificate), ['signature' => self::SIGNATURE])->assertNotFound();
        $this->asPeer()->get(route('qualitycertificates.index'))
            ->assertInertia(fn (Assert $page) => $page->where('counts.all', 0)->has('record.data', 0));

        // The dossier shows the same release rule, and the validator sees who did each stage.
        $this->as('releaser')->get(route('qualitycertificates.show', $certificate))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('QualityCertificates/Show')
                ->where('release.ready', true)->where('release.results', 2)->where('release.approved', 2)
                ->where('release.sample.url', route('vap_samples.show', $entry->id))
                ->where('release.proposal.url', route('vap-proposals.show', $proposal->id)));
        $this->as('releaser')->withHeader('X-Modal', '1')->get(route('qualitycertificates.getApprove', $certificate))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('QualityCertificates/validation-modal')
                ->where('release.ready', true)
                ->has('results', 2)
                ->where('results.0.value', '7.2')
                ->where('results.0.inserted.by', $this->people['analyst']->name)
                ->where('results.0.verified.by', $this->people['verifier']->name)
                ->where('results.0.approved.by', $this->people['approver']->name)
                ->whereNot('results.0.approved.at', null));

        // 6. Validation is signed once; afterwards the certificate is locked.
        $this->as('releaser')->post(route('qualitycertificates.approve', $certificate), ['signature' => self::SIGNATURE])
            ->assertRedirect(route('qualitycertificates.show', $certificate));
        $this->as('releaser')->post(route('qualitycertificates.approve', $certificate), ['signature' => self::SIGNATURE])
            ->assertRedirect(route('qualitycertificates.show', $certificate));
        $certificate->refresh();
        $this->assertSame($this->people['releaser']->id, $certificate->validated_by_id);
        $this->assertCount(1, $certificate->getMedia('validation_signature'));

        $this->people['approver']->givePermissionTo(Permission::findOrCreate('validate_quality_certificates', 'web'));
        $this->as('approver')->post(route('qualitycertificates.approve', $certificate), ['signature' => self::SIGNATURE])
            ->assertSessionHasErrors('signature');
        $this->as('releaser')->put(route('qualitycertificates.update', $certificate), ['obs' => 'Tentativa após validação'])
            ->assertSessionHasErrors('obs');
        $this->as('releaser')->get(route('qualitycertificates.edit', $certificate))->assertRedirect(route('qualitycertificates.show', $certificate));
        $this->assertSame('issued', $this->dossierStage($proposal));
    }

    public function test_a_certificate_cannot_be_validated_while_a_result_is_unapproved(): void
    {
        [$entry, $analysis] = $this->registeredSample();
        $rows = $this->resultRows('analyst', $analysis, 'analyze', 'inserted_value', '3.1');
        $this->as('analyst')->post(route('results.store'), ['action' => 'analyze', 'sample_id' => $analysis->sample_id, 'results' => $rows])->assertSessionHasNoErrors();
        $certificate = app(AnalysisReportService::class)->ensureForCollectionProduct($entry->collectionProduct, $this->people['releaser']->id);

        $this->as('releaser')->post(route('qualitycertificates.approve', $certificate), ['signature' => self::SIGNATURE])
            ->assertSessionHasErrors('signature');
        $this->assertNull($certificate->fresh()->validated_at);
        $this->assertCount(0, $certificate->fresh()->getMedia('validation_signature'));
    }

    public function test_direct_certificate_release_requires_current_owning_lab_membership(): void
    {
        $certificate = $this->approvedCertificate();
        $this->expectException(AuthorizationException::class);

        app(ValidateQualityCertificate::class)->execute($this->lab->id, $this->peer->id, $certificate->id, self::SIGNATURE);
    }

    public function test_direct_certificate_release_rejects_an_unverified_validator(): void
    {
        $certificate = $this->approvedCertificate();
        $this->people['releaser']->forceFill(['email_verified_at' => null])->save();
        $this->expectException(AuthorizationException::class);

        app(ValidateQualityCertificate::class)->execute($this->lab->id, $this->people['releaser']->id, $certificate->id, self::SIGNATURE);
    }

    public function test_certificate_save_veto_cannot_create_release_signature_or_success_evidence(): void
    {
        $certificate = $this->approvedCertificate();
        $auditCount = DB::table('activity_log')->where('subject_type', $certificate->getMorphClass())->where('subject_id', $certificate->id)->count();
        $dispatcher = QualityCertificate::getEventDispatcher();
        QualityCertificate::setEventDispatcher(clone $dispatcher);
        QualityCertificate::saving(fn (QualityCertificate $record): ?bool => $record->is($certificate) && $record->isDirty('validated_at') ? false : null);

        try {
            app(ValidateQualityCertificate::class)->execute($this->lab->id, $this->people['releaser']->id, $certificate->id, self::SIGNATURE);
            $this->fail('A vetoed certificate write must fail release.');
        } catch (LogicException $exception) {
            $this->assertSame('Certificate release evidence was not persisted.', $exception->getMessage());
        } finally {
            QualityCertificate::setEventDispatcher($dispatcher);
        }

        $this->assertNull($certificate->fresh()->validated_at);
        $this->assertCount(0, $certificate->fresh()->getMedia('validation_signature'));
        $this->assertSame($auditCount, DB::table('activity_log')->where('subject_type', $certificate->getMorphClass())->where('subject_id', $certificate->id)->count());
    }

    public function test_a_colleague_signs_on_behalf_of_an_absent_validator(): void
    {
        $certificate = $this->approvedCertificate();
        $absent = User::factory()->create(['is_active' => true, 'name' => 'E2E absent validator']);
        $absent->givePermissionTo(Permission::findOrCreate('validate_quality_certificates', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $absent->id]);
        $outsider = User::factory()->create(['is_active' => true]);
        $outsider->givePermissionTo(Permission::findOrCreate('validate_quality_certificates', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $this->peerLab->id, 'user_id' => $outsider->id]);
        $unauthorised = $this->people['approver'];

        $attempt = fn (?int $userId, bool $onBehalf = true) => $this->as('releaser')->post(route('qualitycertificates.approve', $certificate),
            ['signature' => self::SIGNATURE, 'approve_on_behalf_of' => $onBehalf, 'signed_by_user_id' => $userId]);

        $attempt(null)->assertSessionHasErrors('signed_by_user_id');
        $attempt($this->people['releaser']->id)->assertSessionHasErrors('signed_by_user_id');
        $attempt($outsider->id)->assertSessionHasErrors('signed_by_user_id');
        $attempt($unauthorised->id)->assertSessionHasErrors('signed_by_user_id');
        $this->assertNull($certificate->fresh()->validated_at);
        $this->assertCount(0, $certificate->fresh()->getMedia('validation_signature'));

        $attempt($absent->id)->assertRedirect(route('qualitycertificates.show', $certificate))->assertSessionHasNoErrors();
        $certificate->refresh();
        $this->assertSame($this->people['releaser']->id, $certificate->validated_by_id);
        $this->assertTrue((bool) $certificate->validated_on_behalf_of);
        $this->assertSame($absent->id, $certificate->validated_on_behalf_of_id);
        $this->assertCount(1, $certificate->getMedia('validation_signature'));
        $this->as('releaser')->get(route('qualitycertificates.show', $certificate))
            ->assertInertia(fn (Assert $page) => $page->where('record.data.validated_on_behalf_of_user', $absent->name));
    }

    public function test_a_proposal_created_outside_the_authoring_form_still_gets_a_public_link(): void
    {
        $template = VAPProposalTemplate::query()->create(['name' => 'Modelo importado', 'content' => '<p>Condições</p>', 'user_id' => $this->people['commercial']->id, 'is_active' => true]);
        $proposal = new VAPProposal;
        $proposal->forceFill(['lab_id' => $this->lab->id, 'customer_id' => $this->customer->id, 'warehouse_id' => $this->site->id, 'template_id' => $template->id,
            'department_id' => $this->department->id, 'user_id' => $this->people['commercial']->id, 'status' => 'PENDING'])->save();

        $this->assertTrue(Str::isUuid($proposal->unique_hash));
        $this->as('commercial')->get(route('vap-proposals.show', $proposal))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('VAPProposals/Show')->where('proposal.unique_hash', $proposal->unique_hash));
    }

    public function test_the_verifier_cannot_also_approve(): void
    {
        [, $analysis] = $this->registeredSample();
        $this->as('analyst')->post(route('results.store'), ['action' => 'analyze', 'sample_id' => $analysis->sample_id, 'results' => $this->resultRows('analyst', $analysis, 'analyze', 'inserted_value', '1')])->assertSessionHasNoErrors();
        $this->as('verifier')->post(route('results.store'), ['action' => 'verify', 'sample_id' => $analysis->sample_id, 'results' => $this->resultRows('verifier', $analysis, 'verify', 'verified_value', '1'), 'signature' => self::SIGNATURE])->assertSessionHasNoErrors();
        $this->people['verifier']->givePermissionTo(Permission::findOrCreate('approve_results', 'web'));
        $this->qualify($this->people['verifier'], 'approve_results');

        $this->as('verifier')->post(route('results.store'), ['action' => 'approve', 'sample_id' => $analysis->sample_id, 'results' => $this->resultRows('verifier', $analysis, 'approve', 'approved_value', '1'), 'signature' => self::SIGNATURE])
            ->assertSessionHasErrors('results');
        $this->assertSame(0, Result::query()->where('sample_id', $analysis->sample_id)->whereNotNull('approved_date')->count());
    }

    private function catalogue(): void
    {
        $category = AnalysisCategory::query()->create(['name' => 'Físico-química E2E '.Str::random(4), 'code' => 'FQ-'.Str::random(6), 'department_id' => $this->department->id]);
        $matrix = Matrix::query()->create(['code' => 'AGUA-'.Str::random(6), 'description' => 'Água de processo E2E']);
        $this->profile = Profile::query()->create(['name' => 'Físico-química básica E2E', 'code' => 'PFQ-'.Str::random(6), 'category_id' => $category->id]);
        $refs = [];
        foreach (['unit' => Unit::class, 'protocol' => Protocol::class, 'standard' => Standard::class, 'nwp' => NormativeWorkProcedure::class] as $key => $class) {
            $reference = $class::query()->create(['name' => $key.' E2E '.Str::random(4), 'code' => strtoupper($key).'-'.Str::random(6)]);
            $refs[$key.'_id'] = $reference->id;
            $refs[$key.'_label'] = $reference->code;
        }
        $resultCategory = ResultCategory::query()->create(['name' => 'Categoria E2E '.Str::random(4)]);
        foreach (['pH a 20 °C', 'Condutividade'] as $name) {
            $parameter = Parameter::query()->create(['name' => $name.' E2E', 'code' => 'P-'.Str::random(6), 'active' => true]);
            $this->profile->parameters()->attach($parameter->id, [...$refs, 'category_id' => $resultCategory->id, 'category_label' => $resultCategory->name]);
        }
        $matrix->profiles()->attach($this->profile->id);
        $this->product = Product::query()->create(['name' => 'Água de processo E2E', 'matrix_id' => $matrix->id]);
    }

    /** @return array{0: VAPSampleEntry, 1: Analysis} */
    private function registeredSample(): array
    {
        $this->as('reception')->post(route('vap_samples.samples.store'), [
            'name' => 'Amostra directa E2E', 'sample_type' => 'AGUA', 'status' => 'POR_INICIAR',
            'customer_id' => $this->customer->id, 'warehouse_id' => $this->site->id, 'department_id' => $this->department->id,
            'lab_id' => $this->lab->id, 'received_at' => now()->toDateTimeString(),
            'client_submitted_info' => ['request_origin' => 'client', 'collection_type' => 'direct', 'product_id' => $this->product->id,
                'matrix_id' => $this->product->matrix_id, 'requested_profile_ids' => [$this->profile->id]],
        ])->assertSessionHasNoErrors();
        $entry = VAPSampleEntry::query()->where('name', 'Amostra directa E2E')->latest('id')->firstOrFail();

        return [$entry, Analysis::query()->whereIn('sample_id', data_get($entry->client_submitted_info, 'linked_sample_ids', []))->sole()];
    }

    private function approvedCertificate(): QualityCertificate
    {
        [$entry, $analysis] = $this->registeredSample();
        $stages = [['analyst', 'analyze', 'inserted_value'], ['verifier', 'verify', 'verified_value'], ['approver', 'approve', 'approved_value']];
        foreach ($stages as [$role, $action, $field]) {
            $this->as($role)->post(route('results.store'), ['action' => $action, 'sample_id' => $analysis->sample_id,
                'results' => $this->resultRows($role, $analysis, $action, $field, '5'), 'signature' => self::SIGNATURE])->assertSessionHasNoErrors();
        }

        return app(AnalysisReportService::class)->ensureForCollectionProduct($entry->collectionProduct, $this->people['releaser']->id);
    }

    /** @return array<int, array<string, mixed>> */
    private function resultRows(string $role, Analysis $analysis, string $action, string $field, string $value): array
    {
        $rows = $this->as($role)->getJson(route('results.getDefaultResultsData', ['sample_id' => $analysis->sample_id, 'action' => $action]))
            ->assertOk()->json();
        $this->assertNotEmpty($rows);

        $review = $action === 'analyze' ? [] : ['uncertainty_value' => '0.1'];

        return array_map(fn (array $row): array => [...$row, $field => $value, ...$review], $rows);
    }

    private function dossierStage(VAPProposal $proposal): string
    {
        $response = $this->as('commercial')->get(route('laboratory-workflow.index'))->assertOk();

        return (string) data_get(collect(data_get($response->viewData('page'), 'props.dossiers', []))->firstWhere('id', $proposal->id), 'stage.key');
    }

    private function qualify(User $user, string $capability): void
    {
        PersonnelQualification::query()->updateOrCreate(
            ['lab_id' => $this->lab->id, 'user_id' => $user->id, 'capability' => $capability, 'department_id' => $this->department->id],
            ['qualified_by_id' => $user->id, 'authorized_from' => now()->subDay()->toDateString(), 'authorized_until' => now()->addYear()->toDateString(),
                'training_completed_at' => now()->subDay()->toDateString(), 'training_reference' => 'E2E-'.$capability, 'is_active' => true],
        );
    }

    private function as(string $role): static
    {
        return $this->actingAs($this->people[$role])->withSession(['active_lab_id' => $this->lab->id]);
    }

    private function asPeer(): static
    {
        return $this->actingAs($this->peer)->withSession(['active_lab_id' => $this->peerLab->id]);
    }
}
