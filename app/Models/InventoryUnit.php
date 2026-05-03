<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class InventoryUnit extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'iunits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'description',
    ];

    protected $table = 'i_units';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

}
