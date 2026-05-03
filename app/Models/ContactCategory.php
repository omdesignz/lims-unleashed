<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class ContactCategory extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'contact_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    protected $table = 'contact_categories';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

}
