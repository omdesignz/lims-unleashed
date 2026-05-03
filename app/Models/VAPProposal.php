<?php

namespace App\Models;

use HighSolutions\EloquentSequence\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Artisan;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class VAPProposal extends Model
{
    use SoftDeletes, LogsActivity, Sequence;

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

    protected $appends = ['proposal_number', 'status_badge', 'days_until_expiry'];

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
            ->setDescriptionForEvent(fn(string $eventName) => "Proposta {$this->proposal_number} foi {$eventName}");
    }

    public function getProposalNumberAttribute(): string
    {
        return "PRO-{$this->proposal_year}-{$this->proposal_no}";
    }

    public function getStatusBadgeAttribute(): array
    {
        $statuses = [
            'PENDING' => ['class' => 'bg-yellow-100 text-yellow-800', 'text' => 'Pending'],
            'SENT' => ['class' => 'bg-blue-100 text-blue-800', 'text' => 'Sent'],
            'VIEWED' => ['class' => 'bg-purple-100 text-purple-800', 'text' => 'Viewed'],
            'ACCEPTED' => ['class' => 'bg-green-100 text-green-800', 'text' => 'Accepted'],
            'REJECTED' => ['class' => 'bg-red-100 text-red-800', 'text' => 'Rejected'],
            'REVISED' => ['class' => 'bg-orange-100 text-orange-800', 'text' => 'Revised'],
            'EXPIRED' => ['class' => 'bg-gray-100 text-gray-800', 'text' => 'Expired'],
        ];

        return $statuses[$this->status] ?? $statuses['PENDING'];
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) return null;
        
        $expiry = \Carbon\Carbon::parse($this->expiry_date);
        $now = \Carbon\Carbon::now();
        
        return $now->diffInDays($expiry, false);
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
        return $this->belongsTo(ProposalTemplate::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProposalItem::class, 'proposal_id');
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
            ->orWhere(function($q) {
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

        static::saving(function ($proposal) {

        });

        static::creating(function (VAPProposal $proposal) {
            $proposal->proposal_no = 'PROP ' . Department::find($proposal->department_id)->name . '/' . $proposal->seq . '/' . $proposal->proposal_year;

            $proposal->details = json_encode(VAPProposalTemplate::find($proposal->template_id)->content);
        });

        // static::created(function ($proposal) {
        //     Artisan::call('app:sign-proposal-with-hash', ['proposal' => $proposal->id]);
        // });

        self::deleting(function ($proposal) {

        });
    }
}
