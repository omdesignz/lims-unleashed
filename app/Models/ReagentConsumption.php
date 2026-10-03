<?php

namespace App\Models;

use App\Filters\GlobalFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\QueryBuilder\AllowedFilter;

class ReagentConsumption extends Model
{
    use HasFactory, LogsActivity;

    public const MENU_NAME = 'reagent_consumption';

    protected $table = 'reagent_consumption';

    protected $fillable = ['lab_id', 'reagent_id', 'reagent_name', 'quantity_used', 'used_by', 'used_at', 'remarks', 'date', 'user_id', 'warehouse_id', 'usage_type', 'project', 'batch_id', 'inventory_transaction_id'];

    protected $casts = [
        'used_at' => 'datetime',
        'date' => 'date',
        'quantity_used' => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $consumption): void {
            if ($consumption->lab_id === null) {
                $consumption->lab_id = InventoryItem::query()->findOrFail($consumption->reagent_id)->lab_id;
            }
        });

        static::updating(function (self $consumption): void {
            if ($consumption->isDirty(['lab_id', 'reagent_id', 'warehouse_id', 'batch_id'])) {
                throw new LogicException('Reagent consumption ownership cannot be reassigned.');
            }
        });
    }

    public function scopeForLaboratory(Builder $query, int $labId): Builder
    {
        return $query->where('reagent_consumption.lab_id', $labId);
    }

    public function scopeUnreversed(Builder $query): Builder
    {
        return $query->whereDoesntHave('reversal');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(ReagentConsumptionReversal::class, 'consumption_id');
    }

    public function reagent()
    {
        return $this->belongsTo(InventoryItem::class, 'reagent_id');
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'reagent_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(InventoryItemWarehouse::class, 'warehouse_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function inventoryTransaction()
    {
        return $this->belongsTo(InventoryTransaction::class, 'inventory_transaction_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['reagent_id', 'quantity_used', 'used_by', 'used_at', 'remarks'])
            ->useLogName('reagent_consumption')
            ->setDescriptionForEvent(fn (string $eventName) => 'Consumo de reagente '.match ($eventName) {
                'created' => 'registado',
                'updated' => 'actualizado',
                'deleted' => 'eliminado',
                'restored' => 'restaurado',
                default => $eventName,
            });
    }

    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('reagent_name'),
            AllowedFilter::partial('quantity_used'),
            AllowedFilter::partial('remarks'),
            AllowedFilter::partial('created_at'),
            AllowedFilter::custom('globalFilter', new GlobalFilter(['reagent_name', 'quantity_used', 'remarks'])),
            AllowedFilter::trashed(),
        ];
    }

    public static function getAllowedSorts(): array
    {
        return [
            'reagent_name',
            'created_at',
        ];
    }

    public static function getColumns(): array
    {
        return [
            [
                'name' => trans('gestlab.general.labels.reagent_consumption.date'),
                'value' => 'date',
                'filter_field' => 'date',
                'filterable' => true,
                'type' => 'string',
                'format' => '',
                'filter' => '',
            ],
            [
                'name' => trans('gestlab.general.labels.reagent_consumption.reagent_name'),
                'value' => 'reagent_name',
                'filter_field' => 'reagent_name',
                'filterable' => true,
                'type' => 'string',
                'format' => '',
                'filter' => '',
            ],
            [
                'name' => trans('gestlab.general.labels.reagent_consumption.quantity_used'),
                'value' => 'quantity_used',
                'filter_field' => 'quantity_used',
                'filterable' => true,
                'type' => 'string',
                'format' => '',
                'filter' => '',
            ],
            [
                'name' => trans('gestlab.general.labels.created_at'),
                'value' => 'created_at',
                'filter_field' => 'created_at',
                'filterable' => true,
                'type' => 'date',
                'format' => '',
                'filter' => '',
            ],
            // [
            //     'name' => trans('gestlab.actions.edit'),
            //     'value' => 'actions',
            //     'filter_field' => 'actions',
            //     'filterable' => false,
            //     'type' => 'actions',
            //     'format' => '',
            //     'filter' => '',
            // ],
        ];
    }
}
