<?php

namespace App\Models;

use Database\Factories\PortalServiceInvitationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class PortalServiceInvitation extends Model
{
    /** @use HasFactory<PortalServiceInvitationFactory> */
    use HasFactory;

    protected $fillable = ['lab_id', 'warehouse_id', 'customer_id', 'issued_by_id', 'token', 'expires_at', 'revoked_at', 'consumed_at', 'customer_request_id'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'revoked_at' => 'datetime', 'consumed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $invitation): void {
            if ($invitation->isDirty(['lab_id', 'warehouse_id', 'customer_id', 'issued_by_id', 'token', 'expires_at'])
                || ($invitation->getOriginal('consumed_at') && $invitation->isDirty(['consumed_at', 'customer_request_id']))) {
                throw new LogicException('Issued service invitation identity cannot be changed.');
            }
        });
    }

    public function scopeAvailableTo(Builder $query, Warehouse $warehouse): Builder
    {
        return $query->where('warehouse_id', $warehouse->id)->where('customer_id', $warehouse->customer_id)
            ->whereNull('revoked_at')->whereNull('consumed_at')->whereNull('customer_request_id')
            ->where('expires_at', '>', now())->whereHas('lab')->whereHas('customer');
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class, 'lab_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
