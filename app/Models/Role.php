<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role as ModelsRole;

class Role extends ModelsRole
{
    use HasFactory;

    public CONST MENU_NAME = 'roles';

    protected $fillable = [
        'name',
        'label',
        'guard_name',
    ];

    public static function addedRoles()
    {
        return [
            'Técnico: Microbiologia',
            'Técnico: Química',
            'Administrativo',
            'Utilizador',
        ];
    }
}
