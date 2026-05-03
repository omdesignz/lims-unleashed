<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VAPFileVersion extends Model
{
    use HasUuids;

    public CONST MENU_NAME = 'v_file_versions';

    protected $table = 'v_file_versions';

    protected $fillable = [
        'file_id',
        'revision_code',
        'content',
        'mime_type',
        'size',
        'checksum',
        'created_by',
        'comment',
        'change_reason',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(VAPFile::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
