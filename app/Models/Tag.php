<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasUuids;

    public CONST MENU_NAME = 'tags';

    protected $table = 'tags';

    protected $fillable = ['name'];

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(VAPFile::class, 'file_tags', 'tag_id', 'file_id')
            ->withTimestamps();
    }
}
