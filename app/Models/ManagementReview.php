<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

class ManagementReview extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lab_id',
        'reference',
        'review_date',
        'status',
        'scope',
        'summary',
        'decisions',
        'risks_and_opportunities',
        'improvements',
        'conducted_by_id',
        'approved_by_id',
        'approved_at',
    ];

    protected $casts = [
        'lab_id' => 'integer',
        'review_date' => 'date',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $review): void {
            if ($review->isDirty('lab_id')) {
                throw new LogicException('A management review cannot change its owning laboratory.');
            }
        });
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class, 'lab_id');
    }

    public function conductedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conducted_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}
