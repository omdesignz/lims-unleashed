<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VAPFile extends Model
{
    use HasUuids;

    public CONST MENU_NAME = 'v_files';

    protected $table = 'v_files';

    protected $fillable = [
        'name',
        'type',
        'size',
        'modified_at',
        'parent_id',
        'mime_type',
        'content',
        'created_by',
        'archived',
        'archived_at',
        'document_number',
        'document_type',
        'category',
        'revision_code',
        'status',
        'confidentiality_level',
        'is_controlled',
        'requires_periodic_review',
        'retention_period_days',
        'effective_at',
        'review_due_at',
        'approved_at',
        'obsolete_at',
        'owner_id',
        'approved_by',
        'superseded_by',
        'change_reason',
        'meta',
    ];

    protected $casts = [
        'size' => 'integer',
        'modified_at' => 'datetime',
        'archived' => 'boolean',
        'archived_at' => 'datetime',
        'is_controlled' => 'boolean',
        'requires_periodic_review' => 'boolean',
        'retention_period_days' => 'integer',
        'effective_at' => 'datetime',
        'review_due_at' => 'datetime',
        'approved_at' => 'datetime',
        'obsolete_at' => 'datetime',
        'meta' => 'array',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(VAPFile::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(VAPFile::class, 'parent_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(VAPFileVersion::class, 'file_id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(VAPFilePermission::class, 'file_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(VAPFileShare::class, 'file_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(VAPFile::class, 'superseded_by');
    }

    public function tags (): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'file_tags', 'file_id', 'tag_id');
    }

    public function accessLevelFor(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        if ((method_exists($user, 'hasRole') && $user->hasRole('admin')) || $this->created_by === $user->id) {
            return 'admin';
        }

        return $this->relationLoaded('permissions')
            ? optional($this->permissions->firstWhere('user_id', $user->id))->access_level
            : $this->permissions()->where('user_id', $user->id)->value('access_level');
    }

    public function canBeReadBy(?User $user): bool
    {
        return ! is_null($this->accessLevelFor($user));
    }

    public function canBeWrittenBy(?User $user): bool
    {
        return in_array($this->accessLevelFor($user), ['write', 'admin'], true);
    }

    public function canBeApprovedBy(?User $user): bool
    {
        return $this->accessLevelFor($user) === 'admin';
    }
}
