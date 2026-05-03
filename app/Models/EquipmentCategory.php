<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class EquipmentCategory extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'equipment_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'code',
        'parent_id',
    ];

    protected $table = 'equipment_categories';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * Parent
     *
     * @return Relationship
     */
    public function parent()
    {
        return $this->belongsTo(EquipmentCategory::class, 'parent_id');
    }

}