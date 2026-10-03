<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\AnalysisCategory;
use App\Models\CollectionProduct;
use App\Models\Customer;
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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SampleEntryCollectionEntrypointTest extends TestCase
{
    use DatabaseTransactions;

    private User $operator;

    private VAPLab $lab;

    private Customer $customer;

    private Warehouse $warehouse;

    private Product $product;

    private Parameter $parameter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->operator = User::factory()->create(['is_active' => true]);
        $this->operator->assignRole(Role::findOrCreate('admin', 'web'));
        $this->lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $this->operator->id]);
        $this->withSession(['active_lab_id' => $this->lab->id]);
        $this->customer = Customer::query()->create(['name' => fake()->unique()->bothify('Intake customer ######')]);
        $this->warehouse = Warehouse::query()->create([
            'name' => fake()->unique()->bothify('Intake site ######'), 'customer_id' => $this->customer->id,
        ]);
        $department = Department::factory()->create();
        $category = AnalysisCategory::query()->create([
            'name' => fake()->unique()->bothify('Intake category ######'),
            'code' => fake()->unique()->bothify('IC-######'), 'department_id' => $department->id,
        ]);
        $matrix = Matrix::query()->create([
            'code' => fake()->unique()->bothify('IM-######'), 'description' => fake()->unique()->bothify('Intake matrix ######'),
        ]);
        $profile = Profile::query()->create([
            'name' => fake()->unique()->bothify('Intake profile ######'),
            'code' => fake()->unique()->bothify('IP-######'), 'category_id' => $category->id,
        ]);
        $matrix->profiles()->attach($profile->id);
        $this->parameter = Parameter::query()->create(['name' => fake()->unique()->bothify('Intake parameter ######'), 'active' => true]);
        $profile->parameters()->attach($this->parameter->id);
        $this->product = Product::query()->create([
            'name' => fake()->unique()->bothify('Intake product ######'), 'matrix_id' => $matrix->id,
        ])->load('matrix.profiles.type');
        Notification::fake();
        Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);
    }

    private function verifiedAdmin(): User
    {
        return $this->operator;
    }

    private function qualifyUser(User $user, Department $department): void
    {
        PersonnelQualification::query()->updateOrCreate(
            [
                'lab_id' => $this->lab->id,
                'user_id' => $user->id,
                'capability' => 'sample_intake_validation',
                'department_id' => $department->id,
            ],
            [
                'qualified_by_id' => $user->id,
                'authorized_from' => now()->subDay()->toDateString(),
                'authorized_until' => now()->addYear()->toDateString(),
                'training_completed_at' => now()->subDay()->toDateString(),
                'training_reference' => 'SAMPLE-ENTRY-COLLECTION',
                'is_active' => true,
            ]
        );
    }

    public function test_programmed_collection_starts_from_sample_entry_and_exposes_lineage(): void
    {
        $user = $this->verifiedAdmin();
        $product = $this->product;

        /** @var Profile $profile */
        $profile = $product->matrix->profiles->first();
        $department = Department::query()->findOrFail($profile->type->department_id);
        $customer = $this->customer;
        $warehouse = $this->warehouse;
        $lab = $this->lab;
        $code = 'PRG-SE-'.Str::upper(Str::random(6));

        $this->qualifyUser($user, $department);

        $this->actingAs($user)
            ->post(route('vap_samples.samples.store'), [
                'name' => 'Programmed Sample Entry Flow',
                'code' => $code,
                'sample_type' => 'ROTINA',
                'customer_id' => $customer->id,
                'lab_id' => $lab->id,
                'department_id' => $department->id,
                'warehouse_id' => $warehouse->id,
                'received_at' => now()->toDateTimeString(),
                'collected_at' => now()->addDay()->toDateTimeString(),
                'status' => 'POR_INICIAR',
                'client_submitted_info' => [
                    'request_origin' => 'client',
                    'collection_type' => 'programmed',
                    'collection_location' => 'Linha de produção 1',
                    'vehicle_reference' => 'Equipa externa',
                    'product_id' => $product->id,
                    'matrix_id' => $product->matrix_id,
                    'requested_profile_ids' => [$profile->id],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('type', 'success');

        $sampleEntry = VAPSampleEntry::query()->where('code', $code)->firstOrFail();

        $this->assertNotNull($sampleEntry->collection_product_id);
        $this->assertSame('programmed', data_get($sampleEntry->client_submitted_info, 'linked_collection_type'));

        $collectionProduct = CollectionProduct::query()
            ->with(['collection.collectionable', 'code', 'sampleEntry'])
            ->findOrFail($sampleEntry->collection_product_id);

        $this->assertSame('programmed', $collectionProduct->collection->collectionable_type);
        $this->assertSame('Linha de produção 1', $collectionProduct->collection->collectionable->collection_location);
        $this->assertSame('Equipa externa', $collectionProduct->collection->collectionable->vehicle_reference);
        $this->assertSame($sampleEntry->id, $collectionProduct->sampleEntry?->id);
        $this->assertTrue(
            Analysis::query()
                ->where('profile_id', $profile->id)
                ->where('product_id', $product->id)
                ->whereIn('sample_id', data_get($sampleEntry->client_submitted_info, 'linked_sample_ids', []))
                ->exists()
        );

        $analysis = Analysis::query()
            ->where('profile_id', $profile->id)
            ->where('product_id', $product->id)
            ->whereIn('sample_id', data_get($sampleEntry->client_submitted_info, 'linked_sample_ids', []))
            ->firstOrFail();

        $this->actingAs($user)
            ->get(route('programmedcollections.show', $collectionProduct))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('DirectCollections/Show')
                ->where('collectionPresentation.type', 'programmed')
                ->where('record.data.sample_entry.id', $sampleEntry->id)
                ->where('record.data.entry_origin.is_sample_entry_first', true)
                ->where('record.data.entry_origin.collection_type', 'programmed')
                ->where('record.data.links.sample_entry_show_path', route('vap_samples.show', $sampleEntry))
            );

        $response = $this->actingAs($user)
            ->get(route('analysis.index', ['category' => 'insert', 'per_page' => 100]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Analysis/Index'));

        $analysisRow = collect(data_get($response->viewData('page'), 'props.record.data', []))
            ->firstWhere('id', $analysis->id);

        $this->assertNotNull($analysisRow);
        $this->assertSame($sampleEntry->id, data_get($analysisRow, 'sample_entry.id'));
        $this->assertSame('programmed', data_get($analysisRow, 'entry_origin.collection_type'));
        $this->assertTrue(data_get($analysisRow, 'entry_origin.is_sample_entry_first'));
        $this->assertSame(route('vap_samples.show', $sampleEntry), data_get($analysisRow, 'links.sample_entry_show_path'));
        $this->assertSame(route('programmedcollections.show', $collectionProduct), data_get($analysisRow, 'links.collection_show_path'));

        $this->actingAs($user)
            ->get(route('vap_samples.show', $sampleEntry))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPSamples/Show')
                ->where('sample.workflow_links.collection_workflow_url', route('programmedcollections.show', $collectionProduct))
                ->where('sample.workflow_links.analysis_queue_url', route('analysis.index', ['category' => 'insert']))
                ->where('workflowSummary.linked_lab_code', $collectionProduct->code?->code)
                ->where('workflowSummary.analysis_queue_category', 'insert')
                ->where('workflowSummary.next_action.url', route('analysis.index', ['category' => 'insert']))
                ->has('analyses.0.counter_analysis_items')
            );
    }

    public function test_manual_sample_entry_batch_registers_multiple_samples_through_normal_flow(): void
    {
        $user = $this->verifiedAdmin();
        $product = $this->product;

        /** @var Profile $profile */
        $profile = $product->matrix->profiles->first();
        $department = Department::query()->findOrFail($profile->type->department_id);
        $customer = $this->customer;
        $warehouse = $this->warehouse;
        $lab = $this->lab;

        $this->qualifyUser($user, $department);

        $response = $this->actingAs($user)
            ->post(route('vap_samples.samples.bulk-store'), [
                'samples' => collect(['A', 'B'])->map(fn (string $suffix) => [
                    'name' => 'Manual Queue Sample '.$suffix,
                    'sample_type' => 'ROTINA',
                    'customer_id' => $customer->id,
                    'lab_id' => $lab->id,
                    'department_id' => $department->id,
                    'warehouse_id' => $warehouse->id,
                    'received_at' => now()->toDateTimeString(),
                    'status' => 'POR_INICIAR',
                    'client_submitted_info' => [
                        'request_origin' => 'client',
                        'collection_type' => 'direct',
                        'product_id' => $product->id,
                        'matrix_id' => $product->matrix_id,
                        'requested_profile_ids' => [$profile->id],
                        'lot' => 'MQ-'.$suffix,
                        'manual_entry' => true,
                    ],
                ])->all(),
            ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('type', 'success');

        $samples = VAPSampleEntry::query()
            ->whereIn('name', ['Manual Queue Sample A', 'Manual Queue Sample B'])
            ->get();

        $this->assertCount(2, $samples);

        $samples->each(function (VAPSampleEntry $sample): void {
            $this->assertNotNull($sample->code);
            $this->assertNotNull($sample->collection_product_id);
            $this->assertTrue((bool) data_get($sample->client_submitted_info, 'manual_batch'));
            $this->assertSame('direct', data_get($sample->client_submitted_info, 'linked_collection_type'));
        });
    }

    public function test_collection_indexes_create_through_sample_entry_defaults(): void
    {
        $user = $this->verifiedAdmin();

        $directResponse = $this->actingAs($user)
            ->get(route('directcollections.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('DirectCollections/Index')
                ->where('query.category', 'pending')
                ->where('entrypoint.create_sample_url', route('vap_samples.index', ['collection_type' => 'direct']))
            );

        $directFields = collect(data_get($directResponse->viewData('page'), 'props.fields', []))->pluck('value');
        $this->assertContains('entry_lineage', $directFields);
        $this->assertContains('tracking_label', $directFields);

        $this->actingAs($user)
            ->get(route('directcollections.index', ['category' => 'unexpected']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('DirectCollections/Index')
                ->where('query.category', 'pending')
            );

        $programmedResponse = $this->actingAs($user)
            ->get(route('programmedcollections.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ProgrammedCollections/Index')
                ->where('query.category', 'pending')
                ->where('entrypoint.create_sample_url', route('vap_samples.index', ['collection_type' => 'programmed']))
            );

        $programmedFields = collect(data_get($programmedResponse->viewData('page'), 'props.fields', []))->pluck('value');
        $this->assertContains('entry_lineage', $programmedFields);
        $this->assertContains('tracking_label', $programmedFields);

        $this->actingAs($user)
            ->get(route('programmedcollections.index', ['category' => 'unexpected']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ProgrammedCollections/Index')
                ->where('query.category', 'pending')
            );

        $this->actingAs($user)
            ->get(route('vap_samples.index', ['collection_type' => 'programmed']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPSamples/Index')
                ->where('entryWorkflowDefaults.collection_type', 'programmed')
            );

        foreach (['directcollections', 'programmedcollections'] as $routePrefix) {
            $this->assertFalse(Route::has($routePrefix.'.create'));
            $this->assertFalse(Route::has($routePrefix.'.store'));
            $this->actingAs($user)->get('/'.$routePrefix.'/create')->assertNotFound();
            $this->postJson(route($routePrefix.'.index'), ['products' => []])->assertStatus(405);
        }

        $this->actingAs($user)
            ->get(route('samples.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Samples/Index')
                ->where('slideOverEdit', false)
                ->where('createAction', false)
                ->where('entrypoint.create_sample_url', route('vap_samples.index'))
            );

        $parameter = $this->parameter;

        $this->actingAs($user)
            ->get(route('samples.index', ['parameters' => [$parameter->id]]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Samples/Index')
                ->has('parameters')
            );

        $this->actingAs($user)
            ->get(route('samples.index', ['filter' => ['parameters' => [$parameter->id]]]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Samples/Index')
            );

        foreach (['create', 'store', 'edit', 'update', 'destroy', 'restore'] as $action) {
            $this->assertFalse(Route::has('samples.'.$action));
        }

        foreach (['create', 'destroy', 'restore', '1/edit'] as $path) {
            $this->actingAs($user)->get('/samples/'.$path)->assertNotFound();
        }

        $this->postJson(route('samples.index'), [])->assertStatus(405);
        $this->putJson('/samples/1', [])->assertNotFound();

        $sampleCodesResponse = $this->actingAs($user)->getJson(route('samples.getCode'));

        $sampleCodesResponse->assertOk();
        $this->assertIsArray($sampleCodesResponse->json());
    }

    public function test_sample_entry_bulk_import_creates_entries_and_collection_lineage(): void
    {
        $user = $this->verifiedAdmin();
        $product = $this->product;

        /** @var Profile $profile */
        $profile = $product->matrix->profiles->first();
        $department = Department::query()->findOrFail($profile->type->department_id);
        $customer = $this->customer;
        $warehouse = $this->warehouse;
        $lab = $this->lab;
        $code = 'BULK-SE-'.Str::upper(Str::random(6));

        $this->qualifyUser($user, $department);

        $csv = implode("\n", [
            'name,code,sample_type,request_origin,collection_type,customer_id,lab_id,department_id,warehouse_id,product_id,requested_profile_ids,received_at,quantity,collected_qty,lot,origin,location,temperature_value,sampling_plan_ref,status',
            sprintf(
                '"Bulk Imported Sample","%s","MATERIA_PRIMA","internal","direct",%d,%d,%d,%d,%d,%d,"%s","2 kg","3 frascos","BULK-L-001","Fornecedor auditado","Sala de receção","Ambiente controlado","Plano BULK-ISO","POR_INICIAR"',
                $code,
                $customer->id,
                $lab->id,
                $department->id,
                $warehouse->id,
                $product->id,
                $profile->id,
                now()->format('Y-m-d H:i:s')
            ),
        ]);

        $file = UploadedFile::fake()->createWithContent('sample-entry-import.csv', $csv);

        $this->actingAs($user)
            ->post(route('vap_samples.samples.import'), ['file' => $file])
            ->assertRedirect()
            ->assertSessionHas('type', 'success');

        $sampleEntry = VAPSampleEntry::query()->where('code', $code)->firstOrFail();
        $this->assertNotNull($sampleEntry->collection_product_id);
        $this->assertSame('Fornecedor auditado', data_get($sampleEntry->client_submitted_info, 'origin'));
        $this->assertSame('Plano BULK-ISO', data_get($sampleEntry->client_submitted_info, 'sampling_plan_ref'));

        $collectionProduct = CollectionProduct::query()->findOrFail($sampleEntry->collection_product_id);
        $this->assertSame('BULK-L-001', $collectionProduct->lot);
        $this->assertSame('Plano BULK-ISO', $collectionProduct->sampling_plan_ref);
        $this->assertSame('Ambiente controlado', $collectionProduct->temperature_value);
    }

    public function test_sample_entry_bulk_import_accepts_portuguese_human_headings_and_names(): void
    {
        $user = $this->verifiedAdmin();
        $product = $this->product;

        /** @var Profile $profile */
        $profile = $product->matrix->profiles->first();
        $department = Department::query()->findOrFail($profile->type->department_id);
        $customer = $this->customer;
        $warehouse = $this->warehouse;
        $lab = $this->lab;
        $code = 'BULK-PT-'.Str::upper(Str::random(6));

        $this->qualifyUser($user, $department);

        $csv = implode("\n", [
            'Nome da amostra,Código da amostra,Tipo de amostra,Origem do trabalho,Fluxo de colheita,Cliente,Laboratório,Departamento,Armazém,Produto,Matriz,Perfis analíticos,Recebido em,Quantidade recebida,Lote,Origem,Local de colheita,Temperatura,Plano de amostragem,Estado',
            sprintf(
                '"Amostra importada por nomes","%s","MATERIA_PRIMA","interno","direta","%s","%s","%s","%s","%s","%s","%s","%s","2 kg","PT-L-001","Fornecedor nacional","Sala técnica","Ambiente","Plano PT-ISO","POR_INICIAR"',
                $code,
                $customer->name,
                $lab->name,
                $department->name,
                $warehouse->name ?: $warehouse->address,
                $product->name,
                $product->matrix->description,
                $profile->name,
                now()->format('Y-m-d H:i:s')
            ),
        ]);

        $file = UploadedFile::fake()->createWithContent('sample-entry-import-pt.csv', $csv);

        $this->actingAs($user)
            ->post(route('vap_samples.samples.import'), ['file' => $file])
            ->assertRedirect()
            ->assertSessionHas('type', 'success');

        $sampleEntry = VAPSampleEntry::query()->where('code', $code)->firstOrFail();

        $this->assertSame($customer->id, $sampleEntry->customer_id);
        $this->assertSame($lab->id, $sampleEntry->lab_id);
        $this->assertSame($department->id, $sampleEntry->department_id);
        $this->assertSame($warehouse->id, $sampleEntry->warehouse_id);
        $this->assertSame($product->id, data_get($sampleEntry->client_submitted_info, 'product_id'));
        $this->assertContains($profile->id, data_get($sampleEntry->client_submitted_info, 'requested_profile_ids', []));
        $this->assertSame('PT-L-001', data_get($sampleEntry->client_submitted_info, 'lot'));
        $this->assertNotNull($sampleEntry->collection_product_id);
    }

    public function test_analysis_and_counter_analysis_creation_use_canonical_workflows(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->get(route('analysis.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Analysis/Index')
                ->where('entrypoint.create_sample_url', route('vap_samples.index'))
            );

        $this->actingAs($user)
            ->get(route('analysis.index', ['category' => 'unexpected']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Analysis/Index')
                ->where('query.category', 'insert')
            );

        $this->actingAs($user)
            ->get(route('counteranalysis.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('CounterAnalysis/Index')
                ->where('entrypoint.analysis_url', route('analysis.index', ['category' => 'insert']))
            );

        $this->actingAs($user)
            ->get(route('analysis.create'))
            ->assertRedirect(route('vap_samples.index'))
            ->assertSessionHas('toast.message', 'As novas análises devem começar pela entrada de amostra para manter a rastreabilidade completa.');

        $this->actingAs($user)
            ->get(route('counteranalysis.create'))
            ->assertRedirect(route('analysis.index', ['category' => 'insert']))
            ->assertSessionHas('toast.message', 'Solicite a contra-análise a partir de um resultado existente.');
    }

    public function test_sample_entry_destroy_archives_with_traceability_metadata(): void
    {
        $user = $this->verifiedAdmin();
        $sampleEntry = VAPSampleEntry::factory()->create(['lab_id' => $this->lab->id, 'customer_id' => $this->customer->id]);

        $this->from(route('vap_samples.index'))
            ->actingAs($user)
            ->delete(route('vap_samples.samples.destroy', $sampleEntry), [
                'reason' => 'smoke_archive',
            ])
            ->assertRedirect(route('vap_samples.index'))
            ->assertSessionHas('type', 'success');

        $this->assertSoftDeleted('sample_entries', [
            'id' => $sampleEntry->id,
        ]);

        $archivedSampleEntry = VAPSampleEntry::withTrashed()->findOrFail($sampleEntry->id);

        $this->assertSame($user->id, data_get($archivedSampleEntry->client_submitted_info, 'archived_by.user_id'));
        $this->assertSame('smoke_archive', data_get($archivedSampleEntry->client_submitted_info, 'archived_by.reason'));
    }
}
