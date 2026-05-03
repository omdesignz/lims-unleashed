<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class TaxType extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'tax_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'percent',
        'compound_tax',
        'collective_tax',
        'description',
        'user_id',
    ];

    protected $table = 'tax_types';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'compound_tax' => 'boolean',
        'collective_tax' => 'boolean',
    ];

}
