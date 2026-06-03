<?php

namespace App\Models;

use App\Notifications\PortalPasswordResetNotification;
use App\Notifications\PortalVerifyEmailNotification;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys as HasPasskeysContract;

class Warehouse extends Authenticatable implements HasPasskeysContract, MustVerifyEmail
{
    use HasFactory, MustVerifyEmailTrait, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    public const MENU_NAME = 'warehouses';

    protected $guard = 'portal';

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
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function sendPasswordResetNotification($token): void
    {
        $url = route('portal.password.reset', [
            'token' => $token,
            'email' => $this->email,
        ]);

        $this->notify(new PortalPasswordResetNotification($url));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new PortalVerifyEmailNotification);
    }

    public function passkeys(): HasMany
    {
        return $this->hasMany(Passkey::class, 'authenticatable_id')
            ->where('authenticatable_type', self::class)
            ->withAttributes(['authenticatable_type' => self::class]);
    }

    public function getPassKeyName(): string
    {
        return $this->email ?: $this->code ?: 'warehouse-'.$this->getKey();
    }

    public function getPassKeyId(): string
    {
        return 'warehouse-'.$this->getKey();
    }

    public function getPassKeyDisplayName(): string
    {
        return $this->customer?->name ?: $this->name ?: $this->email ?: 'Cliente '.$this->getKey();
    }
}
