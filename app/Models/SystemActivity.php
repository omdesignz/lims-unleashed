<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class SystemActivity extends Activity
{
    use HasFactory;

    public CONST MENU_NAME = 'activity_log';

    protected $table = 'activity_log';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'changes' => 'json'
    ];
}
