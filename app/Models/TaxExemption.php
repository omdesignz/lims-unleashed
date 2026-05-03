<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class TaxExemption extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'tax_exemptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'reason',
        'law',
        'description',
        'user_id',
    ];

    protected $table = 'tax_exemptions';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

}
