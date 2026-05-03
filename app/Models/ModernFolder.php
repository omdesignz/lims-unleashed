<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kalnoy\Nestedset\NodeTrait;

class ModernFolder extends Model
{
    /** @use HasFactory<\Database\Factories\ModernFolderFactory> */
    use HasFactory;
    use NodeTrait;

    protected $fillable = ['name', 'slug', 'parent_id', 'path', 'user_id'];

    public function files()
    {
        return $this->hasMany(File::class, 'folder_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subfolders()
    {
        return $this->hasMany(ModernFolder::class, 'parent_id');
    }

    // Define the many-to-many relationship with User
    public function sharedWithUsers()
    {
        return $this->belongsToMany(User::class, 'shared_folders', 'folder_id', 'user_id')
                    ->withTimestamps();
    }
}
