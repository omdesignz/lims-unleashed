<?php
// app/Models/SampleDiscard.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VAPSampleDiscard extends Model
{
    use SoftDeletes;

    protected $table = 'sample_discards';

    protected $fillable = [
        'lab_id',
        'department_id',
        'sample_id',
        'discard_method',
        'discarded_by_id',
        'qty',
        'discarded_at',
    ];

    protected $casts = [
        'discarded_at' => 'datetime',
    ];

    // Relationships
    public function sample(): BelongsTo
    {
        return $this->belongsTo(VAPSampleEntry::class, 'sample_id');
    }

    public function discardedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discarded_by_id');
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // Scopes
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('discard_method', $method);
    }

    // Events
    protected static function booted()
    {
        static::creating(function ($discard) {
            if (!$discard->discarded_by_id && auth()->check()) {
                $discard->discarded_by_id = auth()->id();
            }
            
            if (!$discard->discarded_at) {
                $discard->discarded_at = now();
            }
        });
    }
}