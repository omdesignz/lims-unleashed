<?php

namespace App\Models;

use Carbon\Carbon;
use HighSolutions\EloquentSequence\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class VAPProposal extends Model
{
    use LogsActivity, Sequence, SoftDeletes;

    protected $table = 'proposals';

    protected $fillable = [
        'seq',
        'proposal_year',
        'proposal_no',
        'service_location',
        'customer_id',
        'warehouse_id',
        'department_id',
        'user_id',
        'template_id',
        'status',
        'details',
        'is_original',
        'discount_type',
        'file_path',
        'obs',
        'sub_total',
        'total',
        'unique_hash',
        'use_matrix_price',
        'tolerance_days',
        'withholding_tax_amount',
        'withholding_tax_percentage',
        'global_discount_amount',
        'global_discount_percentage',
        'withhold_tax',
        'converted_to_invoice',
    ];

    protected $casts = [
        'details' => 'array',
        'sub_total' => 'decimal:2',
        'total' => 'decimal:2',
        'withholding_tax_amount' => 'decimal:2',
        'withholding_tax_percentage' => 'decimal:2',
        'global_discount_amount' => 'decimal:2',
        'global_discount_percentage' => 'decimal:2',
        'is_original' => 'boolean',
        'use_matrix_price' => 'boolean',
        'withhold_tax' => 'boolean',
        'converted_to_invoice' => 'boolean',
        'deleted_at' => 'datetime',
        'expiry_date' => 'date',
    ];

    protected $appends = ['proposal_number', 'status_badge', 'days_until_expiry', 'tax', 'discount'];

    public function sequence()
    {
        return [
            'group' => 'proposal_year',
            'fieldName' => 'seq',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'obs', 'total', 'sub_total'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Proposta {$this->proposal_number} foi {$eventName}");
    }

    public function getProposalNumberAttribute(): string
    {
        if (filled($this->proposal_no)) {
            return (string) $this->proposal_no;
        }

        $year = $this->proposal_year ?: now()->year;
        $sequence = $this->seq ? str_pad((string) $this->seq, 4, '0', STR_PAD_LEFT) : str_pad((string) $this->getKey(), 4, '0', STR_PAD_LEFT);

        return "PROP {$year}/{$sequence}";
    }

    public function getStatusBadgeAttribute(): array
    {
        $statuses = [
            'PENDING' => ['class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-500/15 dark:text-yellow-200', 'text' => 'Pendente'],
            'SENT' => ['class' => 'bg-blue-100 text-blue-800 dark:bg-blue-500/15 dark:text-blue-200', 'text' => 'Enviada'],
            'VIEWED' => ['class' => 'bg-purple-100 text-purple-800 dark:bg-purple-500/15 dark:text-purple-200', 'text' => 'Vista'],
            'ACCEPTED' => ['class' => 'bg-green-100 text-green-800 dark:bg-emerald-500/15 dark:text-emerald-200', 'text' => 'Aceite'],
            'REJECTED' => ['class' => 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-200', 'text' => 'Rejeitada'],
            'REVISED' => ['class' => 'bg-orange-100 text-orange-800 dark:bg-orange-500/15 dark:text-orange-200', 'text' => 'Revista'],
            'EXPIRED' => ['class' => 'bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-slate-200', 'text' => 'Expirada'],
        ];

        return $statuses[$this->status] ?? $statuses['PENDING'];
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        $expiry = Carbon::parse($this->expiry_date);
        $now = Carbon::now();

        return $now->diffInDays($expiry, false);
    }

    public function getTaxAttribute(): float
    {
        if (array_key_exists('items_sum_tax_amount', $this->attributes)) {
            return (float) $this->attributes['items_sum_tax_amount'];
        }

        if ($this->relationLoaded('items')) {
            return (float) $this->items->sum(fn (VAPProposalItem|ProposalItem $item) => (float) $item->tax_amount);
        }

        if (! $this->exists) {
            return 0.0;
        }

        return (float) $this->items()->sum('tax_amount');
    }

    public function getDiscountAttribute(): float
    {
        if (array_key_exists('items_sum_discount_amount', $this->attributes)) {
            return (float) $this->attributes['items_sum_discount_amount'];
        }

        if ($this->relationLoaded('items')) {
            return (float) $this->items->sum(fn (VAPProposalItem|ProposalItem $item) => (float) $item->discount_amount);
        }

        if (! $this->exists) {
            return 0.0;
        }

        return (float) $this->items()->sum('discount_amount');
    }

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(VAPProposalTemplate::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(VAPProposalItem::class, 'proposal_id');
    }

    public function complianceAgreement(): HasOne
    {
        return $this->hasOne(VAPProposalComplianceAgreement::class, 'proposal_id');
    }

    public function complianceAgreementLogs(): HasMany
    {
        return $this->hasMany(VAPProposalComplianceAgreementLog::class, 'proposal_id');
    }

    public function discountCategory(): BelongsTo
    {
        return $this->belongsTo(DiscountCategory::class, 'discount_type');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['PENDING', 'SENT', 'VIEWED', 'REVISED']);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'EXPIRED')
            ->orWhere(function ($q) {
                $q->where('expiry_date', '<', now())
                    ->whereIn('status', ['PENDING', 'SENT', 'VIEWED']);
            });
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'ACCEPTED');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function (VAPProposal $proposal) {
            $departmentName = Department::query()->whereKey($proposal->department_id)->value('name') ?: 'LAB';
            $year = $proposal->proposal_year ?: now()->year;
            $sequence = $proposal->seq ? str_pad((string) $proposal->seq, 4, '0', STR_PAD_LEFT) : '0001';

            $proposal->proposal_year = $year;
            $proposal->proposal_no = $proposal->proposal_no ?: "PROP {$departmentName}/{$sequence}/{$year}";

            if (blank($proposal->details)) {
                $proposal->details = [
                    'template_content' => VAPProposalTemplate::query()->whereKey($proposal->template_id)->value('content'),
                ];
            }
        });
    }
}
