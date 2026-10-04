<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

class CustomerRequest extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = 'customer_requests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'lab_id',
        'reference',
        'title',
        'request_type',
        'status',
        'priority',
        'preferred_date',
        'submitted_at',
        'resolved_at',
        'description',
        'contact',
        'email',
        'category_id',
        'customer_id',
        'answered',
        'warehouse_id',
        'extra_data',
    ];

    protected $table = 'customer_requests';

    protected static function booted(): void
    {
        static::updating(function (self $record): void {
            if ($record->isDirty(['lab_id', 'customer_id', 'warehouse_id'])) {
                throw new LogicException('Service request laboratory and recipient identity cannot be changed.');
            }
        });
    }

    public function scopeForLaboratory(Builder $query, int $labId): Builder
    {
        return $query->where('lab_id', $labId)->whereHas('lab');
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class, 'lab_id');
    }

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'answered' => 'boolean',
        'submitted_at' => 'datetime',
        'resolved_at' => 'datetime',
        'preferred_date' => 'date',
        'extra_data' => AsCollection::class,
    ];

    /**
     * Customer Request Category
     *
     * @return Relationship
     */
    public function category()
    {
        return $this->belongsTo(CustomerRequestCategory::class, 'category_id');
    }

    /**
     * Customer
     *
     * @return Relationship
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Warehouse
     *
     * @return Relationship
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function getPortalStatusAttribute(): string
    {
        if ($this->status) {
            return $this->status;
        }

        return $this->answered ? 'completed' : 'pending';
    }
}
