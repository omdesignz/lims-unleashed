<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;

class ProposalComplianceAgreement extends Model
{
    use HasFactory, SoftDeletes;

    //
    protected $fillable = [
        'proposal_id',
        'confidentiality',
        'impartiality',
        'nondisclosure',
        'acknowledged_at',
        'client_ip',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('proposal_id'),
            AllowedFilter::trashed(),
        ];
    }

    public static function getAllowedSorts(): array
    {
        return [
            'proposal_id',
            'created_at',
        ];
    }

    public static function getColumns(): array
    {
        return [
            [
                'name' => trans('gestlab.general.labels.proposal_compliance_agreements.proposal_id'),
                'value' => 'proposal',
                'filter_field' => 'proposal_id',
                'filterable' => true,
                'type' => 'string',
                'format' => '',
                'filter' => '',
                'options' => [],
                'config' => [
                    'url' => route('vap-proposals.options.proposals'),
                    'label' => 'proposal_no',
                    'value' => 'id',
                ],
            ],
            [
                'name' => trans('gestlab.general.labels.proposal_compliance_agreements.confidentiality'),
                'value' => 'confidentiality',
            ],
            [
                'name' => trans('gestlab.general.labels.proposal_compliance_agreements.impartiality'),
                'value' => 'impartiality',
            ],
            [
                'name' => trans('gestlab.general.labels.proposal_compliance_agreements.nondisclosure'),
                'value' => 'nondisclosure',
            ],
            [
                'name' => trans('gestlab.general.labels.proposal_compliance_agreements.acknowledged_at'),
                'value' => 'acknowledged_at',
            ],
            [
                'name' => trans('gestlab.general.labels.proposal_compliance_agreements.client_ip'),
                'value' => 'client_ip',
            ],
            [
                'name' => trans('gestlab.actions.edit'),
                'value' => 'actions',
                'filter_field' => 'actions',
                'filterable' => false,
                'type' => 'actions',
                'format' => '',
                'filter' => '',
            ],
        ];
    }
}
