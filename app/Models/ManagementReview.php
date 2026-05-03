<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ManagementReview extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
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
        'review_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function conductedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conducted_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}
