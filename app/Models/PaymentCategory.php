<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class PaymentCategory extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'payment_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'code',
    ];

    protected $table = 'payment_categories';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}