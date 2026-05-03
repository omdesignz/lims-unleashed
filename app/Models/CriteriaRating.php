<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CriteriaRating extends Model
{
    const MENU_NAME = 'criteria_rating';
    
    protected $table = 'criteria_rating';

    protected $fillable = [
        'name',
        'description',
        'type'
    ];
}
