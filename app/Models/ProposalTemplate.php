<?php

namespace App\Models;

use App\Filters\GlobalFilter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;

class ProposalTemplate extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = 'proposal_templates';

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
                'value' => 'name',
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
                ],
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
            ['value' => 'with', 'text' => trans('gestlab.general.labels.trashed_with')],
        ];
    }

    public static function defaultTerms()
    {
        return '
            ### Confidencialidade
            Garantimos que todos os dados e resultados do cliente serão tratados com estrita confidencialidade e não serão divulgados a terceiros sem consentimento prévio.

            ### Imparcialidade
            O nosso laboratório actua com imparcialidade, integridade e independência para garantir resultados e serviços isentos.

            ### Termos do acordo
            Ao aceitar esta proposta, o cliente concorda em cumprir os termos e condições indicados, incluindo, entre outros:
            - As condições de pagamento especificadas.
            - A entrega das amostras em condições adequadas.
            - O cumprimento dos requisitos da ISO/IEC 17025.

            ### Responsabilidade
            O laboratório não poderá ser responsabilizado por danos causados pelo manuseamento inadequado das amostras pelo cliente.
        ';
    }
}
