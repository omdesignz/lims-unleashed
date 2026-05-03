<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Variable extends Model
{
    use HasFactory;

    public const MENU_NAME = 'variables';

    protected $guarded = [];

    public function formula(): BelongsTo
    {
        return $this->belongsTo(Formula::class);
    }
}
