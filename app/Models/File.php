<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class File extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'path', 'folder_id', 'user_id', 'size', 'mime_type', 'extension'];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(ModernFolder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(FileVersion::class);
    }

    public function currentVersion(): HasOne
    {
        return $this->hasOne(FileVersion::class)->latest();
    }
}
