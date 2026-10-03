<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class ReagentConsumptionPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (['add_reagent_consumption' => 'Registar consumo de reagentes',
                'delete_reagent_consumption' => 'Reverter consumo de reagentes'] as $name => $label) {
                Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['label' => $label]);
            }
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
