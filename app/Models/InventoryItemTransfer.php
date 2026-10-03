<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

class InventoryItemTransfer extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = 'itransfers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'lab_id',
        'qty',
        'sent_date',
        'received_date',
        'obs',
        'item_id',
        'source_id',
        'destination_id',
        'expected_date',
    ];

    protected $table = 'i_transfers';

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'qty' => 'decimal:4',
        'sent_date' => 'date',
        'received_date' => 'date',
        'expected_date' => 'date',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $transfer): void {
            if ($transfer->isDirty(['lab_id', 'item_id', 'source_id', 'destination_id', 'qty', 'sent_date'])) {
                throw new LogicException('Issued inventory transfer identity cannot be reassigned.');
            }
        });
    }

    /**
     * Item
     *
     * @return Relationship
     */
    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    /**
     * Item Origin Warehouse
     *
     * @return Relationship
     */
    public function from()
    {
        return $this->belongsTo(InventoryItemWarehouse::class, 'source_id');
    }

    /**
     * Item Destination Warehouse
     *
     * @return Relationship
     */
    public function to()
    {
        return $this->belongsTo(InventoryItemWarehouse::class, 'destination_id');
    }

    public function source()
    {
        return $this->belongsTo(InventoryItemWarehouse::class, 'source_id');
    }

    public function destination()
    {
        return $this->belongsTo(InventoryItemWarehouse::class, 'destination_id');
    }

    public function scopePending($query)
    {
        return $query->whereNull('received_date');
    }

    public function scopeSent($query)
    {
        return $query->whereNotNull('sent_date')->whereNull('received_date');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('received_date');
    }

    public function scopeOverdue($query)
    {
        return $query->whereNull('received_date')
            ->whereNotNull('expected_date')
            ->where('expected_date', '<', now());
    }

    public function getStatusAttribute()
    {
        if ($this->received_date) {
            return 'completed';
        } elseif ($this->sent_date) {
            return 'in_transit';
        } else {
            return 'pending';
        }
    }

    public function getIsOverdueAttribute()
    {
        return $this->expected_date &&
               ! $this->received_date &&
               $this->expected_date < now();
    }

    public function getDaysOverdueAttribute()
    {
        if (! $this->is_overdue) {
            return 0;
        }

        return now()->diffInDays($this->expected_date);
    }
}
