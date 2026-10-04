<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class NonConformityPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['resolve' => 'Resolver', 'verify' => 'Verificar', 'close' => 'Encerrar', 'reopen' => 'Reabrir'] as $ability => $label) {
            Permission::firstOrCreate(['name' => $ability.'_non_conformities', 'guard_name' => 'web'], ['label' => $label.' não conformidades']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
