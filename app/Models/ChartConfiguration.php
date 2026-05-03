<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartConfiguration extends Model
{
    public CONST MENU_NAME = 'chart_configurations';
    
    protected $fillable = ['chart_type', 'default_settings'];
    protected $casts = [
        'default_settings' => 'array',
    ];
}
