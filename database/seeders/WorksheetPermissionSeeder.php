<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class WorksheetPermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['view', 'add', 'edit', 'delete', 'restore'] as $ability) {
            Permission::findOrCreate($ability.'_worksheets', 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
