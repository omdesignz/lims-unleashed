<?php

namespace App\Models;

use App\Traits\HasProfilePhoto;
use Carbon\Carbon;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Image\Enums\Fit;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys as HasPasskeysContract;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia, HasPasskeysContract, MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasPermissions, HasProfilePhoto, HasRoles, InteractsWithMedia, MustVerifyEmailTrait, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    public const MENU_NAME = 'users';

    public const ABILITIES = ['view', 'add', 'edit', 'delete', 'restore', 'ban', 'reset-password', 'impersonate'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'google_id',
        'github_id',
        'microsoft_id',
        'x_id',
        'microsoft_data',
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'profile_photo_path',
        'email_verified_at',
        'username',
        'password_changed_at',
        'password_changed_by_user',
        'is_active',
        'primary_phone',
        'secondary_phone',
        'id_number',
        'dob',
        'photo',
        'gender',
        'last_login_at',
        'last_activity_at',
        'theme',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $table = 'users';

    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'last_login_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'dob' => 'date',
        'is_active' => 'boolean',
        'password_changed_by_user' => 'boolean',
        'microsoft_data' => 'array',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
        'signature_url',
    ];

    /**
     * Get the user's age.
     *
     * @param  string  $value
     * @return string
     */
    public function getAgeAttribute()
    {
        return $this->dob ? Carbon::parse($this->dob)->age : 0;
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        return $this?->getMedia('avatar')->count() ? $this?->getMedia('avatar')?->first()->getFullUrl() : '';
    }

    public function getDashboardHeaderImageAttribute(): string
    {
        return $this?->getMedia('dashboard_header_image')->count() ? $this?->getMedia('dashboard_header_image')?->first()->getFullUrl('dashboard_header_image') : '';
    }

    public function getSignatureUrlAttribute(): string
    {
        return $this?->getMedia('signature')->count() ? $this?->getMedia('signature')?->first()->getFullUrl() : '';
    }

    public function scopeIsBirthday()
    {
        return $this->dob->isBirthday();
    }

    /**
     * Collaborations
     *
     * @return Relationship
     */
    public function collaborations()
    {
        return $this->belongsToMany(CollectionCollaboration::class, 'col_collab', 'collection_id', 'collaboration_id');
    }

    /**
     * Departments
     *
     * @return Relationship
     */
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_user', 'user_id', 'department_id');
    }

    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }

    public function passkeys(): HasMany
    {
        return $this->hasMany(Passkey::class, 'authenticatable_id')
            ->where('authenticatable_type', self::class)
            ->withAttributes(['authenticatable_type' => self::class]);
    }

    public function getPassKeyName(): string
    {
        return $this->email;
    }

    public function getPassKeyId(): string
    {
        return (string) $this->getKey();
    }

    public function getPassKeyDisplayName(): string
    {
        return $this->name ?: $this->email;
    }

    public function personnelQualifications(): HasMany
    {
        return $this->hasMany(PersonnelQualification::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('exports')
            ->acceptsMimeTypes(
                [
                    'image/jpeg',
                    'text/plain',
                    'application/xml',
                    'image/png',
                    'application/pdf',
                    'text/csv',
                    'text/xml',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])
            ->useDisk('public');

        $this->addMediaCollection('avatar')
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
            ])
            ->useFallbackUrl('/images/avatar-1.jpg')
            ->useFallbackPath(public_path('/images/avatar-1.jpg'))
            ->singleFile();

        $this->addMediaCollection('signature')
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
            ])
            ->singleFile();

        $this->addMediaCollection('dashboard_header_image')
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
            ])
            ->singleFile();

    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('dashboard_header_image')
            ->performOnCollections('dashboard_header_image')
            ->fit(Fit::Crop, 1280, 400)
            ->nonQueued();
    }

    public function getAbilities()
    {

        return self::ABILITIES;

    }

    public function reagentConsumptions()
    {
        return $this->hasMany(ReagentConsumption::class, 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function hasActiveQualificationFor(string $capability, ?int $departmentId = null): bool
    {
        if (! $this->relationLoaded('personnelQualifications')) {
            $this->load('personnelQualifications');
        }

        $qualifications = $this->personnelQualifications
            ->where('is_active', true)
            ->filter(function (PersonnelQualification $qualification) use ($capability, $departmentId) {
                if (! in_array($qualification->capability, [$capability, '*'], true)) {
                    return false;
                }

                if ($qualification->authorized_from && $qualification->authorized_from->isFuture()) {
                    return false;
                }

                if ($qualification->authorized_until && $qualification->authorized_until->isPast()) {
                    return false;
                }

                if ($departmentId !== null && $qualification->department_id !== null && (int) $qualification->department_id !== (int) $departmentId) {
                    return false;
                }

                return true;
            });

        if ($qualifications->isNotEmpty()) {
            return true;
        }

        return $this->personnelQualifications()->count() === 0;
    }

    /**
     * @return array{
     *     total:int,
     *     active:int,
     *     expired:int,
     *     expiring_soon:int,
     *     ready_for_renewal:int,
     *     missing_evidence:int,
     *     qualifications: array<int, array{
     *         id:int,
     *         capability:string,
     *         status:string,
     *         renewal_readiness:string,
     *         follow_up_state:string,
     *         days_until_expiry:?int
     *     }>
     * }
     */
    public function competenceSummary(): array
    {
        if (! $this->relationLoaded('personnelQualifications')) {
            $this->load('personnelQualifications');
        }

        $qualifications = $this->personnelQualifications->map(function (PersonnelQualification $qualification) {
            return [
                'id' => $qualification->id,
                'capability' => $qualification->capability,
                'status' => $qualification->monitoringStatus(),
                'renewal_readiness' => $qualification->renewalReadiness(),
                'follow_up_state' => $qualification->followUpState(),
                'days_until_expiry' => $qualification->daysUntilExpiry(),
            ];
        });

        return [
            'total' => $qualifications->count(),
            'active' => $qualifications->where('status', 'active')->count(),
            'expired' => $qualifications->where('status', 'expired')->count(),
            'expiring_soon' => $qualifications->whereIn('status', ['expiring_critical', 'expiring_soon'])->count(),
            'ready_for_renewal' => $qualifications->where('renewal_readiness', 'ready_for_review')->count(),
            'missing_evidence' => $qualifications->where('renewal_readiness', 'missing_evidence')->count(),
            'qualifications' => $qualifications->values()->all(),
        ];
    }
}
