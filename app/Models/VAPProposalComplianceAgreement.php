<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VAPProposalComplianceAgreement extends Model
{
    use SoftDeletes;

    protected $table = 'proposal_compliance_agreements';

    protected $fillable = [
        'proposal_id',
        'confidentiality',
        'impartiality',
        'nondisclosure',
        'acknowledged_at',
        'rejected_at',
        'rejection_reason',
        'client_ip',
    ];

    protected $casts = [
        'confidentiality' => 'boolean',
        'impartiality' => 'boolean',
        'nondisclosure' => 'boolean',
        'acknowledged_at' => 'datetime',
        'rejected_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(VAPProposal::class);
    }
}
