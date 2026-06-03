<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\LaravelPasskeys\Models\Passkey as BasePasskey;

class Passkey extends BasePasskey
{
    public function authenticatable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'authenticatable_type', 'authenticatable_id');
    }
}
