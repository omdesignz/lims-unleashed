<?php

namespace App\Models;

use Database\Factories\ReagentConsumptionReversalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ReagentConsumptionReversal extends Model
{
    /** @use HasFactory<ReagentConsumptionReversalFactory> */
    use HasFactory;

    protected $fillable = ['lab_id', 'consumption_id', 'inventory_transaction_id', 'user_id', 'reversed_at'];

    protected function casts(): array
    {
        return ['reversed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Issued consumption reversals cannot be edited.'));
        static::deleting(fn () => throw new LogicException('Issued consumption reversals cannot be deleted.'));
    }

    public function consumption(): BelongsTo
    {
        return $this->belongsTo(ReagentConsumption::class, 'consumption_id');
    }

    public function inventoryTransaction(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class, 'inventory_transaction_id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
