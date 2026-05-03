<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Console\Command;
use App\Services\ModelsListingService;

class authPermissionFromModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:permission-from-models';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create authorization permissions for default models';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // $abilities = ['view', 'add', 'edit', 'delete', 'restore'];
        $abilities = config('gestlab.default_abilities');

        $names = collect( (new ModelsListingService())->getModels() )->map(function($item) {
                
            $model = !str()->contains($item, 'Settings') ? 'App?Models?'. $item : 'App?Settings?'. $item;
            $model = str_replace('?','\\',$model);
            
    
            return [
                'model' => $model::MENU_NAME,
                'abilities' => method_exists($model, 'getAbilities') ? $model::ABILITIES : null
            ];
    
        })->filter(function($item) {
            return !is_null($item['model']);
        })->values();


        foreach($names as $name) {

            if(is_null($name['abilities'])) {

                $arr = array_map(function($val) use ($name) {
                    return $val . '_'. $name['model'];
                }, $abilities);

            } else {

                $arr = array_map(function($val) use ($name) {
                    return $val . '_'. $name['model'];
                }, $name['abilities']);

            }

            foreach ($arr as $permission) {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'label' => trans('gestlab.actions.' . str()->before($permission, '_')) . ' ' . strtolower(trans('gestlab.menu.' . str()->after($permission, '_'))),
                ]);
            }
        }

        $this->info(trans('gestlab.console.auth_permission_from_models.permissions_successfully_created'));

        // Sync permissions for admin role
        if( $role = Role::where('name', 'admin')->first() ) {
            $role->syncPermissions(Permission::all());

            $this->info(trans('gestlab.console.auth_permission_from_models.admin_permissions_successfully_updated')); 
        }
    }
}