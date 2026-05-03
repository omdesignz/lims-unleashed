<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use App\Filters\GlobalFilter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProposalTemplate extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'proposal_templates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'content',
        'user_id',
    ];

    protected $table = 'proposal_templates';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('name'),
            AllowedFilter::partial('user_id'),
            AllowedFilter::partial('created_at'),
            AllowedFilter::custom('globalFilter', new GlobalFilter(['name'])),
            AllowedFilter::trashed(),
        ];
    }

    public static function getAllowedSorts(): array
    {
        return [
            'name',
            'created_at',
        ];
    }

    public static function getColumns(): array
    {
        return [
                [
                    'name' => trans('gestlab.general.labels.proposal_templates.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.proposal_templates.user_id'),
                    'value' => 'user',
                    'filter_field' => 'user_id',
                    'filterable' => true,
                    'type' => 'remote_select',
                    'format' => '',
                    'filter' => '',
                    'options' => [],
                    'config' => [
                        'url' => route('users.getUser'),
                        'label' => 'name',
                        'value' => 'id',
                    ]
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

    public static function getTrashedOptions(): array
    {
        return [
            ['value' => 'only', 'text' => trans('gestlab.general.labels.trashed_only')],
            ['value' => 'with', 'text' => trans('gestlab.general.labels.trashed_with')]
        ];
    }
    

    public static function defaultTerms()
    {
        return "
            ### Confidentiality
            We ensure all client data and results will be treated with strict confidentiality and will not be disclosed to any third party without prior consent.

            ### Impartiality
            Our laboratory operates with impartiality, integrity, and independence to ensure unbiased results and services.

            ### Agreement Terms
            By accepting this proposal, you agree to comply with the terms and conditions outlined, including but not limited to:
            - Payment terms as specified.
            - Submission of samples in proper condition.
            - Adherence to ISO 17025 standards.

            ### Liability
            The laboratory will not be held liable for damages caused by improper sample handling by the client.
        ";
    }
}
