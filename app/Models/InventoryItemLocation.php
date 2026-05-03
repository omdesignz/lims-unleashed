<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class InventoryItemLocation extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'ilocations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'address',
        'department_id',
    ];

    protected $table = 'i_locations';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

}
