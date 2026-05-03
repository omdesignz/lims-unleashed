<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'client_ip',
    ];

    protected $casts = [
        'confidentiality' => 'boolean',
        'impartiality' => 'boolean',
        'nondisclosure' => 'boolean',
        'acknowledged_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(VAPProposal::class);
    }
}