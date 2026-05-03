<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Department extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'departments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'contact',
        'extension',
        'code',
        'supervisor_id',
        'email',
    ];

    protected $table = 'departments';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * Supervisor
     *
     * @return Relationship
     */
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

}
