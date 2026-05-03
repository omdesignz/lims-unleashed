<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VAPFilePermission extends Model
{
    use HasUuids;

    public CONST MENU_NAME = 'v_file_permissions';

    protected $table = 'v_file_permissions';

    protected $fillable = [
        'file_id',
        'user_id',
        'access_level',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(VAPFile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}