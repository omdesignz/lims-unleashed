<?php

namespace App\Models;

use HighSolutions\EloquentSequence\Sequence;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


class InventoryItem extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, Sequence;

    public const MENU_NAME = 'iitems';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'code',
        'seq',
        'brand',
        'location',
        'model',
        'software',
        'firmware',
        'internal_code',
        'range',
        'precision',
        'resolution',
        'metrological_uncertainty_value',
        'metrological_uncertainty_unit',
        'metrological_traceability_reference',
        'barcode',
        'serial_number',
        'last_calibration_date',
        'next_calibration_date',
        'metrology_review_due_at',
        'reorder_qty',
        'packed_weight',
        'packed_weight_unit',
        'packed_height',
        'packed_height_unit',
        'packed_width',
        'packed_width_unit',
        'packed_depth',
        'packed_depth_unit',
        'refrigerated',
        'status_id', 
        'has_safety_documentation',
        'packaging_type_id',
        'category_id',
        'department_id',
        'unit_id',
        'eq_cat_id',
        'supplier_id',
        'type_id',
        'lot',
        'reagent_open_date',
        'reagent_expiry_date',
        'user_id',
        'obs',
        'acceptance_criteria',
        'metrology_notes',
        'is_reagent',
        'last_purchase_price',
        'standard_cost',
    ];

    protected $table = 'i_items';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'refrigerated' => 'boolean',
        'is_reagent' => 'boolean',
        'has_safety_documentation' => 'boolean',
        'deleted_at' => 'datetime',
        'reagent_expiry_date' => 'date',
        'reagent_open_date' => 'date',
        'next_calibration_date' => 'date',
        'last_calibration_date' => 'date',
        'metrology_review_due_at' => 'date',
        'packed_depth' => 'decimal:2',
        'packed_width' => 'decimal:2',
        'packed_height' => 'decimal:2',
        'packed_weight' => 'decimal:2',
        'reorder_qty' => 'decimal:2',
        'metrological_uncertainty_value' => 'decimal:4',
    ];

    public function sequence()
    {
        return [
            'group' => ['category_id', 'seq'],
            'fieldName' => 'seq',
        ];
    }

    /**
     * Item Category
     *
     * @return Relationship
     */
    public function category()
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    /**
     * Item Unit
     *
     * @return Relationship
     */
    public function unit()
    {
        return $this->belongsTo(InventoryUnit::class, 'unit_id');
    }

    /**
     * Item Type
     *
     * @return Relationship
     */
    public function type()
    {
        return $this->belongsTo(InventoryItemType::class, 'type_id');
    }


    /**
     * Item Supplier
     *
     * @return Relationship
     */
    public function supplier()
    {
        return $this->belongsTo(InventoryItemSupplier::class, 'supplier_id');
    }

    /**
     * Item Status
     *
     * @return Relationship
     */
    public function status()
    {
        return $this->belongsTo(ItemStatus::class, 'status_id');
    }


    public function warehouses()
    {
        return $this->belongsToMany(InventoryItemWarehouse::class, 'inventory', 'item_id', 'warehouse_id')
            ->withPivot('qty_available', 'min_stock_level', 'reorder_point', 'status')
            ->withTimestamps();
    }


    public function inventory()
    {
        return $this->hasMany(Inventory::class, 'item_id');
    }

    public function orders()
    {
        return $this->belongsToMany(InventoryOrder::class, 'i_order_details', 'item_id', 'order_id')
            ->withPivot('qty', 'expected_date', 'actual_date', 'warehouse_id', 'status')
            ->withTimestamps();
    }

    public function transfers()
    {
        return $this->hasMany(InventoryItemTransfer::class, 'item_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'item_id');
    }

    public function reagentConsumptions()
    {
        return $this->hasMany(ReagentConsumption::class, 'reagent_id');
    }

    public function scopeEquipment($query)
    {
        return $query->whereHas('category', function ($q) {
            $q->where('name', 'like', '%equipamentos%');
        });
    }

    public function scopeReagents($query)
    {
        return $query->whereHas('category', function ($q) {
            $q->where('name', 'like', '%reagentes%');
        });
    }

    public function scopeConsumables($query)
    {
        return $query->whereHas('category', function ($q) {
            $q->where('name', 'like', '%consumíveis%');
        });
    }

    public function getTotalStockAttribute()
    {
        return $this->inventory()->sum('qty_available');
    }

    public function getIsReagentAttribute()
    {
        return $this->category && stripos($this->category->name, 'reagentes') !== false;
    }

    public function getIsExpiredAttribute()
    {
        if (!$this->is_reagent || !$this->reagent_expiry_date) {
            return false;
        }
        
        return now()->gt($this->reagent_expiry_date);
    }

    public function getDaysToExpiryAttribute()
    {
        if (!$this->is_reagent || !$this->reagent_expiry_date) {
            return null;
        }
        
        return now()->diffInDays($this->reagent_expiry_date, false);
    }

    public function getNeedsCalibrationAttribute()
    {
        if (!$this->next_calibration_date) {
            return false;
        }
        
        return now()->gt($this->next_calibration_date);
    }


    public function getCalibrationStatusAttribute()
    {
        if (!$this->next_calibration_date) {
            return 'not_required';
        }
        
        $daysRemaining = now()->diffInDays($this->next_calibration_date, false);
        
        if ($daysRemaining < 0) {
            return 'overdue';
        } elseif ($daysRemaining <= 30) {
            return 'due_soon';
        } else {
            return 'up_to_date';
        }
    }

    public function getRequiresMetrologyControlAttribute(): bool
    {
        return $this->eq_cat_id !== null
            || $this->next_calibration_date !== null
            || $this->last_calibration_date !== null
            || $this->metrological_uncertainty_value !== null
            || filled($this->metrological_traceability_reference);
    }

    public function getMetrologyStatusAttribute(): string
    {
        if (! $this->requires_metrology_control) {
            return 'not_required';
        }

        if (
            $this->metrological_uncertainty_value === null
            || blank($this->metrological_uncertainty_unit)
            || blank($this->metrological_traceability_reference)
        ) {
            return 'incomplete';
        }

        if (
            ($this->next_calibration_date && now()->gt($this->next_calibration_date))
            || ($this->metrology_review_due_at && now()->gt($this->metrology_review_due_at))
        ) {
            return 'hold';
        }

        if (
            ($this->next_calibration_date && now()->diffInDays($this->next_calibration_date, false) <= 30)
            || ($this->metrology_review_due_at && now()->diffInDays($this->metrology_review_due_at, false) <= 30)
        ) {
            return 'review_due';
        }

        return 'validated';
    }

    public function getIsMetrologicallyReadyAttribute(): bool
    {
        return in_array($this->metrology_status, ['not_required', 'validated', 'review_due'], true);
    }

    public function eq_cat()
    {
        return $this->belongsTo(EquipmentCategory::class, 'eq_cat_id');
    } 

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Packaging Type
     *
     * @return Relationship
     */
    public function packagingType()
    {
        return $this->belongsTo(PackagingCategory::class, 'packaging_type_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function maintenance_tasks()
    {
        return $this->hasMany(MaintenanceTask::class, 'equipment_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
            // ->acceptsMimeTypes(
            //     [
            //         'image/jpeg',
            //         'text/plain',
            //         'application/xml',
            //         'image/png',
            //         'application/pdf',
            //         'text/csv',
            //         'text/xml',
            //         'application/vnd.ms-excel',
            //         'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            //     ])
            ->useDisk('public');
    }

    public function getInventoryItemDocuments() {
        return $this?->getMedia('documents') ?? [];
    }

    public static function boot()
    {
        parent::boot();

            static::creating(function($item) {

                $item->internal_code = self::mapInventoryItemCategory()[$item->category_id] . '-' . str_pad ($item->seq, 3, '0', STR_PAD_LEFT);
        
            });

    }

    protected static function mapInventoryItemCategory()
    {
        return [
            '1' => 'LCLD',
            '2' => 'LCLDR',
            '3' => 'LCLDC',
            '4' => 'LCLDV',
        ];
    }
    
}
