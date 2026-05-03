<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VAPFileShare extends Model
{
    use HasUuids;

    public CONST MENU_NAME = 'v_file_shares';

    protected $table = 'v_file_shares';

    protected $fillable = [
        'file_id',
        'shared_with',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(VAPFile::class);
    }

    public function sharedWithUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with');
    }
}