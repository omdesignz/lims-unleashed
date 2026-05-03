<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complaint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference',
        'title',
        'description',
        'status',
        'severity',
        'confidentiality_level',
        'reported_by_name',
        'reported_by_email',
        'customer_id',
        'warehouse_id',
        'assigned_to_id',
        'related_request_id',
        'received_at',
        'acknowledged_at',
        'resolved_at',
        'root_cause',
        'corrective_action',
        'follow_up_notes',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function relatedRequest(): BelongsTo
    {
        return $this->belongsTo(CustomerRequest::class, 'related_request_id');
    }
}
