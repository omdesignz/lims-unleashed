<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Console\Command;


class authPermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:permission {name} {--R|remove} {--M|models}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create authorization permissions for a given model';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $permissions = $this->generatePermissions();

        // check if its remove
        if( $is_remove = $this->option('remove') ) {
            // remove permission
            if( Permission::where('name', 'LIKE', '%'. $this->getNameArgument())->delete() ) {
                $this->warn('Permissions ' . implode(', ', $permissions) . ' deleted.');
            }  else {
                $this->warn('No permissions found!');
            }

        } else {
            // create permissions

            // dd($permissions);

            foreach ($permissions as $permission) {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'label' => trans('gestlab.actions.' . str()->before($permission, '_')) . ' ' . strtolower(trans('gestlab.menu.' . str()->after($permission, '_'))),
                ]);
            }

            $this->info('Permissions ' . implode(', ', $permissions) . ' created.');
        }


        // sync role for admin
        if( $role = Role::where('name', 'Admin')->first() ) {
            $role->syncPermissions(Permission::all());

            $this->info('Admin permissions updated.');
        }
    }

    /**
     * Build permissions from name
     *
     * @return array
     */
    private function generatePermissions()
    {
        $abilities = ['view', 'add', 'edit', 'delete', 'restore'];

        $name = $this->getNameArgument();

        return array_map(function($val) use ($name) {
            return $val . '_'. $name;
        }, $abilities);
    }

    /**
     * Get pluralized name argument
     *
     * @return string
     */
    private function getNameArgument()
    {
        // return strtolower(str()->plural($this->argument('name')));
        return strtolower($this->argument('name'));
    }
}
