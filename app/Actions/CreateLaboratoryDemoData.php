<?php

namespace App\Actions;

use App\Models\AnalysisCategory;
use App\Models\CollectionEndResult;
use App\Models\Customer;
use App\Models\Department;
use App\Models\LabNetwork;
use App\Models\Matrix;
use App\Models\PackagingCategory;
use App\Models\Parameter;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Profile;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Support\SampleEntryCollectionFlowService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use LogicException;

class CreateLaboratoryDemoData
{
    public const EMAIL = 'demo@gestlab.test';

    public const MARKER = 'lims-demo-v1';

    public const NETWORK_NAME = '[DEMO] Rede laboratorial';

    private const ROLE = 'demo-laboratory-reviewer';

    public function __construct(private readonly SampleEntryCollectionFlowService $collectionFlow) {}

    /** @return array{user: User, password: ?string, main_lab: VAPLab, peer_lab: VAPLab, entries: list<VAPSampleEntry>} */
    public function execute(): array
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo data is limited to local and testing environments.');
        }

        return Cache::lock(self::MARKER, 30)->block(5, fn (): array => DB::transaction(function (): array {
            $user = User::withTrashed()->where('email', self::EMAIL)->first();

            if ($user) {
                return $this->existingDataset($user);
            }

            $this->assertIdentifiersAreAvailable();
            $password = Str::random(32);
            $user = User::query()->create([
                'name' => '[DEMO] Gestor do laboratório', 'username' => self::MARKER,
                'email' => self::EMAIL, 'password' => Hash::make($password),
                'is_active' => true, 'email_verified_at' => now(),
                'password_changed_at' => now(), 'password_changed_by_user' => true,
            ]);
            $this->assignDemoPermissions($user);
            $department = Department::query()->create([
                'name' => '[DEMO] Físico-química', 'code' => 'DEMO-FQ', 'description' => self::MARKER,
            ]);
            $user->departments()->attach($department->id);
            $network = LabNetwork::query()->create(['name' => self::NETWORK_NAME, 'primary_color' => '#24664f']);
            $mainLab = VAPLab::query()->create([
                'name' => '[DEMO] Laboratório Central', 'code' => 'DEMO-MAIN',
                'network_id' => $network->id, 'department_id' => $department->id, 'description' => self::MARKER,
            ]);
            $peerLab = VAPLab::query()->create([
                'name' => '[DEMO] Laboratório Norte', 'code' => 'DEMO-NORTH',
                'network_id' => $network->id, 'department_id' => $department->id,
                'description' => self::MARKER, 'primary_color' => '#355c8a',
            ]);
            $network->update(['main_lab_id' => $mainLab->id]);

            foreach ([$mainLab, $peerLab] as $lab) {
                DB::table('lab_user')->insert([
                    'lab_id' => $lab->id, 'user_id' => $user->id,
                    'can_view_network' => $lab->is($mainLab), 'can_manage_branding' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            [$product, $profiles, $parameters, $packaging] = $this->createCatalogue($department);
            $template = VAPProposalTemplate::query()->create([
                'name' => '[DEMO] Condições de demonstração', 'description' => self::MARKER,
                'content' => '<p>[DEMO] Proposta fictícia para revisão da interface. Não enviar ao cliente.</p>',
                'user_id' => $user->id, 'is_active' => true,
            ]);
            $customers = collect(['Indústria Horizonte', 'Águas da Cidade'])->map(function (string $name): array {
                $customer = Customer::query()->create(['name' => '[DEMO] '.$name, 'description' => self::MARKER]);
                $site = Warehouse::query()->create([
                    'name' => '[DEMO] Unidade de '.$name, 'address' => 'Instalação fictícia, Luanda',
                    'customer_id' => $customer->id, 'email' => Str::slug($name).'@gestlab.test',
                ]);

                return [$customer, $site];
            });
            $entries = [];

            foreach ([$mainLab, $peerLab] as $lab) {
                [$customer, $site] = $customers->first();
                $this->createDraftProposal($lab, $user, $department, $customer, $site, $parameters, $template);

                foreach (['direct', 'programmed'] as $index => $type) {
                    [$customer, $site] = $customers->get($index);
                    $entry = VAPSampleEntry::query()->create([
                        'name' => '[DEMO] Água de processo — '.($type === 'direct' ? 'recepção directa' : 'recolha programada'),
                        'lab_id' => $lab->id, 'customer_id' => $customer->id, 'warehouse_id' => $site->id,
                        'department_id' => $department->id, 'packaging_id' => $packaging->id,
                        'received_by_id' => $user->id, 'received_by_label' => $user->name,
                        'sample_type' => 'AGUA', 'status' => 'POR_INICIAR', 'received_at' => now()->subDay(),
                        'collected_at' => now()->subDay(), 'collected_by_lab' => $type === 'programmed',
                        'obs' => '[DEMO] Dados fictícios para revisão da interface. Não emitir documentos reais.',
                        'requested_services' => $profiles->pluck('name')->all(),
                        'client_submitted_info' => [
                            'demo_dataset' => self::MARKER, 'product_id' => $product->id,
                            'requested_profile_ids' => $profiles->modelKeys(), 'collection_type' => $type,
                            'quantity' => 1, 'collected_qty' => 1, 'lot' => 'DEMO-'.$lab->code.'-'.($index + 1),
                            'location' => 'Sector de demonstração', 'origin' => 'Instalação fictícia',
                            'sampling_plan_ref' => 'DEMO-PAM-01', 'chain_of_custody_notes' => 'Recebida selada — demonstração.',
                        ],
                    ]);
                    $collectionProduct = $this->collectionFlow->sync($entry);

                    if (! $collectionProduct) {
                        throw new LogicException('Canonical demo intake could not be created. No demo data was saved.');
                    }

                    $collectionProduct->update(['owner_id' => $user->id]);
                    $entries[] = $entry->fresh();
                }
            }

            return ['user' => $user, 'password' => $password, 'main_lab' => $mainLab, 'peer_lab' => $peerLab, 'entries' => $entries];
        }));
    }

    /** @return array{user: User, password: null, main_lab: VAPLab, peer_lab: VAPLab, entries: list<VAPSampleEntry>} */
    private function existingDataset(User $user): array
    {
        $network = LabNetwork::query()->where('name', self::NETWORK_NAME)->first();
        $labs = VAPLab::withTrashed()->where('network_id', $network?->id)->whereIn('code', ['DEMO-MAIN', 'DEMO-NORTH'])->get();
        $entries = VAPSampleEntry::withTrashed()->where('client_submitted_info->demo_dataset', self::MARKER)
            ->whereIn('lab_id', $labs->modelKeys())->orderBy('id')->get();

        if ($user->trashed() || ! $user->is_active || $user->email_verified_at === null
            || $user->username !== self::MARKER || ! $user->hasRole(self::ROLE)
            || ! $network || $labs->count() !== 2 || $labs->contains(fn (VAPLab $lab): bool => $lab->trashed() || $lab->description !== self::MARKER)
            || $entries->count() !== 4 || $entries->contains(fn (VAPSampleEntry $entry): bool => $entry->collection_product_id === null)) {
            throw new LogicException('Demo identifiers are occupied or the demo dataset was changed. No existing record was modified.');
        }

        if (DB::table('lab_user')->where('user_id', $user->id)->whereIn('lab_id', $labs->modelKeys())->count() !== 2) {
            throw new LogicException('Demo membership was changed. No existing record was modified.');
        }

        return [
            'user' => $user, 'password' => null,
            'main_lab' => $labs->firstWhere('code', 'DEMO-MAIN'),
            'peer_lab' => $labs->firstWhere('code', 'DEMO-NORTH'), 'entries' => $entries->all(),
        ];
    }

    private function assertIdentifiersAreAvailable(): void
    {
        $collision = User::withTrashed()->where('username', self::MARKER)->exists()
            || LabNetwork::query()->where('name', self::NETWORK_NAME)->exists()
            || Role::query()->where('name', self::ROLE)->exists();

        foreach ([
            VAPLab::class, Department::class, Customer::class, Warehouse::class,
            Product::class, Profile::class, Parameter::class, AnalysisCategory::class,
            PackagingCategory::class, CollectionEndResult::class, VAPProposalTemplate::class,
        ] as $model) {
            $collision = $collision || $model::withTrashed()->where('name', 'like', '[DEMO]%')->exists();
        }

        $collision = $collision || Matrix::withTrashed()->where('code', 'DEMO-WATER')->exists()
            || Unit::withTrashed()->where('code', 'like', 'DEMO-%')->exists();

        foreach ([VAPLab::class, Department::class, Profile::class, Parameter::class, AnalysisCategory::class] as $model) {
            $collision = $collision || $model::withTrashed()->where('code', 'like', 'DEMO-%')->exists();
        }

        if ($collision) {
            throw new LogicException('Demo identifiers are already occupied. No existing record was modified.');
        }
    }

    private function assignDemoPermissions(User $user): void
    {
        $permissions = collect([
            'view_samples', 'add_samples', 'edit_samples', 'delete_samples', 'restore_samples',
            'view_direct_collections', 'edit_direct_collections', 'delete_direct_collections', 'restore_direct_collections',
            'view_programmed_collections', 'edit_programmed_collections', 'delete_programmed_collections', 'restore_programmed_collections',
            'view_analysis', 'add_analysis', 'view_proposals', 'view_customers', 'view_warehouses',
            'view_products', 'view_profiles', 'view_parameters', 'view_matrixes', 'view_departments',
            'view_packaging_types', 'view_collection_end_results', 'view_temperatures', 'view_vehicles',
            'view_collection_reasons', 'view_collaboration_categories', 'view_units',
        ])->map(fn (string $permission): Permission => Permission::findOrCreate($permission, 'web'));
        $role = Role::query()->create(['name' => self::ROLE, 'guard_name' => 'web']);
        $role->givePermissionTo($permissions);
        $user->assignRole($role);
    }

    /** @return array{Product, Collection<int, Profile>, Collection<int, Parameter>, PackagingCategory} */
    private function createCatalogue(Department $department): array
    {
        $category = AnalysisCategory::query()->create([
            'name' => '[DEMO] Qualidade da água', 'code' => 'DEMO-WQ', 'department_id' => $department->id,
        ]);
        $matrix = Matrix::query()->create(['code' => 'DEMO-WATER', 'description' => '[DEMO] Água de processo']);
        Unit::query()->create(['code' => 'DEMO-SERVICE', 'description' => 'Ensaio de demonstração']);
        $profileIds = [];
        $parameterIds = [];

        foreach ([['PH', 'pH', 4000, 'pH'], ['COND', 'Condutividade', 8000, 'µS/cm']] as [$code, $name, $price, $unitLabel]) {
            $unit = Unit::query()->create(['code' => 'DEMO-'.$code, 'description' => $unitLabel]);
            $parameter = Parameter::query()->create([
                'name' => '[DEMO] '.$name, 'code' => 'DEMO-'.$code, 'price' => $price, 'active' => true,
                'charge_tax' => false, 'withhold_tax' => false, 'decimal_places' => 2,
            ]);
            $profile = Profile::query()->create([
                'name' => '[DEMO] '.$name, 'code' => 'DEMO-'.$code, 'price' => $price, 'category_id' => $category->id,
            ]);
            $profile->parameters()->attach($parameter->id, ['unit_id' => $unit->id, 'unit_label' => $unitLabel, 'count' => true]);
            $matrix->profiles()->attach($profile->id, ['matrix' => $matrix->code, 'profile' => $profile->name]);
            $profileIds[] = $profile->id;
            $parameterIds[] = $parameter->id;
        }

        $product = Product::query()->create([
            'name' => '[DEMO] Água de processo', 'matrix_id' => $matrix->id, 'description' => self::MARKER,
            'price' => 12000, 'charge_tax' => false, 'withhold_tax' => false,
        ]);
        $packaging = PackagingCategory::query()->create(['name' => '[DEMO] Frasco de 1 litro', 'description' => self::MARKER]);
        CollectionEndResult::query()->create(['name' => '[DEMO] Recebida sem desvios', 'description' => self::MARKER]);

        return [$product, Profile::query()->whereKey($profileIds)->get(), Parameter::query()->whereKey($parameterIds)->get(), $packaging];
    }

    /** @param Collection<int, Parameter> $parameters */
    private function createDraftProposal(VAPLab $lab, User $user, Department $department, Customer $customer, Warehouse $site, Collection $parameters, VAPProposalTemplate $template): void
    {
        $proposal = new VAPProposal;
        $proposal->forceFill([
            'lab_id' => $lab->id, 'customer_id' => $customer->id, 'warehouse_id' => $site->id,
            'department_id' => $department->id, 'user_id' => $user->id, 'template_id' => $template->id, 'status' => 'PENDING',
            'proposal_year' => now()->format('Y'), 'proposal_no' => 'DEMO-PROP-'.$lab->code,
            'details' => ['demo_dataset' => self::MARKER], 'obs' => '[DEMO] Proposta fictícia. Não enviar ao cliente.',
            'sub_total' => $parameters->sum('price'), 'total' => $parameters->sum('price'),
            'is_original' => true, 'converted_to_invoice' => false,
        ])->save();

        $billingUnit = Unit::query()->where('code', 'DEMO-SERVICE')->sole();

        foreach ($parameters as $parameter) {
            $proposal->items()->create([
                'itemable_type' => $parameter->getMorphClass(), 'itemable_id' => $parameter->id,
                'unit_id' => $billingUnit->id,
                'item_description' => $parameter->name, 'qty' => 1, 'unit_price' => $parameter->price,
                'total' => $parameter->price, 'charge_tax' => false, 'withhold_tax' => false,
            ]);
        }
    }
}
