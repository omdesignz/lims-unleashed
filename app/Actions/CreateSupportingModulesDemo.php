<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryNeed;
use App\Models\InventoryUnit;
use App\Models\ItemCategory;
use App\Models\LabNetwork;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceTask;
use App\Models\NotificationTemplate;
use App\Models\Permission;
use App\Models\PersonnelQualification;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPNonConformity;
use App\Models\Warehouse;
use App\Support\NotificationTemplateCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use LogicException;

class CreateSupportingModulesDemo
{
    public function __construct(private readonly NotificationTemplateCatalog $catalog) {}

    /** @return array<string, mixed> */
    public function execute(): array
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demonstration fixtures are restricted to local and testing environments.');
        }

        return DB::transaction(function (): array {
            $marker = 'phase5-'.Str::lower((string) Str::ulid());
            $label = '[DEMO P5 '.substr($marker, -6).']';
            [$staff, $staffPassword] = $this->staff($marker, $label, 'admin');
            $staff->assignRole(Role::findOrCreate('admin', 'web'));
            [$reviewer, $reviewerPassword] = $this->staff($marker, $label, 'reviewer');
            foreach (['view_maintenance_tasks', 'view_iorders', 'view_iitems', 'view_inventory', 'view_activity_log',
                'view_occurrences', 'view_quotes', 'view_customer_requests', 'view_users', 'view_settings'] as $permission) {
                $reviewer->givePermissionTo(Permission::findOrCreate($permission, 'web'));
            }
            [$manager, $managerPassword] = $this->staff($marker, $label, 'lab-manager');
            foreach (['view_users', 'add_users', 'delete_users'] as $permission) {
                $manager->givePermissionTo(Permission::findOrCreate($permission, 'web'));
            }
            [$qualificationEditor, $qualificationEditorPassword] = $this->staff($marker, $label, 'qualification-editor');
            foreach (['view_users', 'edit_users'] as $permission) {
                $qualificationEditor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
            }
            [$peerTarget, $peerTargetPassword] = $this->staff($marker, $label, 'peer-target');

            $department = new Department(['name' => $label.' Departamento', 'code' => $marker]);
            $this->save($department);
            $network = new LabNetwork(['name' => $label.' Rede', 'primary_color' => '#087CF0']);
            $this->save($network);
            $labs = [];
            foreach (['main', 'peer'] as $kind) {
                $lab = new VAPLab([
                    'name' => $label.($kind === 'main' ? ' Laboratório central' : ' Laboratório parceiro'),
                    'code' => $marker.'-'.$kind, 'description' => $marker,
                    'network_id' => $network->id, 'department_id' => $department->id,
                ]);
                $this->save($lab);
                $labs[$kind] = $lab;
                foreach ($this->catalog->definitions() as $key => $definition) {
                    $template = new NotificationTemplate([
                        ...array_intersect_key($definition, array_flip(NotificationTemplate::EDITABLE_FIELDS)),
                        'lab_id' => $lab->id, 'key' => $key, 'updated_by_id' => $staff->id,
                        'channels' => ['database'],
                    ]);
                    $this->save($template);
                }
            }
            $network->main_lab_id = $labs['main']->id;
            $this->save($network);
            foreach ([[$staff, 'main', true], [$reviewer, 'main', true], [$manager, 'main', false], [$qualificationEditor, 'main', false], [$peerTarget, 'peer', false]] as [$user, $labKind, $canViewNetwork]) {
                $user->departments()->attach($department->id);
                DB::table('lab_user')->insert([
                    'lab_id' => $labs[$labKind]->id, 'user_id' => $user->id,
                    'can_view_network' => $canViewNetwork, 'can_manage_branding' => $user->is($staff),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $peerQualification = new PersonnelQualification([
                'lab_id' => $labs['peer']->id, 'user_id' => $peerTarget->id,
                'capability' => $label.' Evidência exclusiva do laboratório parceiro',
                'is_active' => false, 'notes' => 'Registo fictício para verificar a preservação de evidência entre laboratórios.',
            ]);
            $this->save($peerQualification);

            $customer = new Customer(['name' => $label.' Cliente', 'description' => $marker]);
            $this->save($customer);
            $portalPassword = Str::random(40);
            $portal = new Warehouse([
                'name' => $label.' Unidade cliente', 'customer_id' => $customer->id,
                'email' => $marker.'-portal@example.test', 'email_verified_at' => now(), 'description' => $marker,
            ]);
            $portal->forceFill(['password' => Hash::make($portalPassword)]);
            $this->save($portal);

            $unit = new InventoryUnit(['code' => 'P5-'.substr($marker, -6), 'description' => 'Mililitros de demonstração']);
            $this->save($unit);
            $supplier = new InventoryItemSupplier(['name' => $label.' Fornecedor', 'currency' => 'AOA']);
            $this->save($supplier);
            $materialCategory = new ItemCategory(['name' => $label.' Materiais', 'inventory_type' => 'material']);
            $this->save($materialCategory);
            $equipmentCategory = new ItemCategory(['name' => $label.' Equipamentos', 'inventory_type' => 'equipment']);
            $this->save($equipmentCategory);
            $materials = [];
            $warehouses = [];
            foreach ($labs as $kind => $lab) {
                $material = new InventoryItem([
                    'lab_id' => $lab->id, 'name' => $label.' Solução '.$kind, 'code' => $marker.'-'.$kind,
                    'category_id' => $materialCategory->id, 'unit_id' => $unit->id, 'supplier_id' => $supplier->id,
                ]);
                $this->save($material);
                $warehouse = new InventoryItemWarehouse(['lab_id' => $lab->id, 'name' => $label.' Armazém '.$kind]);
                $this->save($warehouse);
                $position = new Inventory([
                    'lab_id' => $lab->id, 'item_id' => $material->id, 'warehouse_id' => $warehouse->id,
                    'qty_available' => $kind === 'main' ? '2.5000' : '10.7500', 'reorder_point' => '3.0000',
                ]);
                $this->save($position);
                $materials[$kind] = $material;
                $warehouses[$kind] = $warehouse;
            }
            $need = new InventoryNeed([
                'lab_id' => $labs['main']->id, 'reference' => 'NEED-'.$marker, 'department_id' => $department->id,
                'requested_by_id' => $staff->id, 'status' => 'submitted', 'submitted_at' => now(),
                'justification' => $label.' Reposição de demonstração. Não enviar ao fornecedor.',
            ]);
            $this->save($need);
            $need->items()->create([
                'inventory_item_id' => $materials['main']->id, 'warehouse_id' => $warehouses['main']->id,
                'quantity_requested' => '0.5000', 'estimated_unit_price' => '100.00', 'status' => 'requested',
            ]);
            $equipment = new InventoryItem([
                'lab_id' => $labs['main']->id, 'name' => $label.' Balança', 'code' => $marker.'-equipment',
                'category_id' => $equipmentCategory->id, 'unit_id' => $unit->id,
            ]);
            $this->save($equipment);
            $calibration = MaintenanceCategory::query()->where('code', 'CAL_INT')->first();
            if (! $calibration) {
                $calibration = new MaintenanceCategory(['name' => 'Calibração interna', 'code' => 'CAL_INT']);
                $this->save($calibration);
            }
            $taskIds = [];
            foreach (['Conforme: verificação de demonstração.', null] as $result) {
                $task = new MaintenanceTask([
                    'name' => $label.($result ? ' Calibração com resultado' : ' Calibração sem resultado'),
                    'equipment_id' => $equipment->id, 'category_id' => $calibration->id, 'supplier_id' => $supplier->id,
                    'due_date' => today(), 'maintenance_task_year' => today()->format('Y'), 'periodicity' => 12,
                    'periodicity_unit' => 'months', 'result' => $result, 'is_executed' => false, 'is_planned' => true,
                ]);
                $this->save($task);
                $taskIds[] = $task->id;
            }
            $nonConformity = new VAPNonConformity([
                'lab_id' => $labs['main']->id, 'nc_number' => 'NC-'.$marker, 'title' => $label.' Desvio de demonstração',
                'description' => 'Registo fictício para verificar o seguimento de acções.', 'status' => 'opened',
                'severity' => 'medium', 'category' => 'quality', 'reported_by' => $staff->name,
                'reported_by_id' => $staff->id, 'reported_at' => now(),
            ]);
            $this->save($nonConformity);

            return [
                'marker' => $marker,
                'staff' => ['id' => $staff->id, 'email' => $staff->email, 'password' => $staffPassword],
                'reviewer' => ['id' => $reviewer->id, 'email' => $reviewer->email, 'password' => $reviewerPassword],
                'lab_manager' => ['id' => $manager->id, 'email' => $manager->email, 'password' => $managerPassword],
                'qualification_editor' => ['id' => $qualificationEditor->id, 'email' => $qualificationEditor->email, 'password' => $qualificationEditorPassword],
                'peer_target' => ['id' => $peerTarget->id, 'email' => $peerTarget->email, 'password' => $peerTargetPassword],
                'peer_qualification_id' => $peerQualification->id,
                'portal' => ['id' => $portal->id, 'email' => $portal->email, 'password' => $portalPassword],
                'main_lab_id' => $labs['main']->id, 'peer_lab_id' => $labs['peer']->id,
                'customer_id' => $customer->id, 'supplier_id' => $supplier->id, 'need_id' => $need->id,
                'maintenance_task_ids' => $taskIds, 'non_conformity_id' => $nonConformity->id,
            ];
        });
    }

    /** @return array{User, string} */
    private function staff(string $marker, string $label, string $kind): array
    {
        $password = Str::random(40);
        $user = new User([
            'name' => $label.' '.$kind, 'username' => $marker.'-'.$kind, 'email' => $marker.'-'.$kind.'@example.test',
            'password' => Hash::make($password), 'is_active' => true, 'email_verified_at' => now(),
            'password_changed_at' => now(), 'password_changed_by_user' => true,
        ]);
        $this->save($user);

        return [$user, $password];
    }

    private function save(Model $model): void
    {
        if (! $model->save()) {
            throw new LogicException('A demo record could not be saved. No fixtures were created.');
        }
    }
}
