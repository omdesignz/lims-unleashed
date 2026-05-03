<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Fortify\TwoFactorAuthenticatable;


class Warehouse extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, SoftDeletes, Notifiable, TwoFactorAuthenticatable;

    public CONST MENU_NAME = 'warehouses';

    protected $guard = "portal";

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'invoicing_email',
        'primary_phone',
        'alternative_phone',
        'address',
        'municipality',
        'name',
        'province',
        'description',
        'focal_point',
        'focal_point_email',
        'focal_point_contact',
        'code',
        'nif',
        'customer_id',
        'email_verified_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $table = 'warehouses';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
    ];

    /**
     * Customer
     *
     * @return Relationship
     */
    public function customer() {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }


}
