<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class InventoryItemType extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'itypes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    protected $table = 'i_types';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

}
