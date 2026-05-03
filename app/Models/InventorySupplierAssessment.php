<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventorySupplierAssessment extends Model
{
    /** @use HasFactory<\Database\Factories\InventorySupplierAssessmentFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'inventory_item_supplier_id',
        'department_id',
        'assessed_by_user_id',
        'assessment_date',
        'next_review_at',
        'status',
        'risk_level',
        'total_score',
        'delivery_score',
        'quality_score',
        'compliance_score',
        'responsiveness_score',
        'evidence_reference',
        'approved_supplier',
        'is_active',
        'strengths',
        'gaps',
        'corrective_actions',
        'follow_up_actions',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assessment_date' => 'date',
            'next_review_at' => 'date',
            'approved_supplier' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(InventoryItemSupplier::class, 'inventory_item_supplier_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by_user_id');
    }
}
