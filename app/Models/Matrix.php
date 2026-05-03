<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Matrix extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'matrixes';

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'description',
        'price',
        'fixed_price',
        'charge_tax',
        'withhold_tax',
        'tax_id',
        'tax_percentage',
        'exemption_id',
        'exemption_code',
    ];

    protected $table = 'matrixes';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];


     /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'charge_tax' => 'boolean',
        'withhold_tax' => 'boolean',
    ];


    /**
     * Profiles
     *
     * @return Relationship
     */
    public function profiles()
    {
        return $this->belongsToMany(Profile::class)->whereNull('matrix_profile.deleted_at')->withPivot('matrix_id', 'matrix', 'profile_id', 'profile')->withTimestamps();
    }

    public function getPriceBasedOnProfilesAttribute()
    {
        $profileIDs = collect($this->profiles)->pluck('id')->toArray();

        $total_price = Profile::whereIn('id', $profileIDs)->sum('price');

        return $total_price ?? 0;
    }


    /**
     * Products
     *
     * @return Relationship
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Paramenters
     *
     * 
     */
    public function scopeParameters($q, $matrix_id)
    {
        return $q->findOrFail($matrix_id)->profiles->map(function($item) {
            return $item->parameters->pluck('id');
        })->flatten()->toArray();
    }


    /**
     * Collections
     *
     * @return Relationship
     */
    public function collections()
    {
        return $this->belongsToMany(Collection::class)->withTrashed();
    }

    public function items()
    {
        return $this->hasMany(MatrixProfile::class)->withTrashed();
    }

     /**
     * Tax Exemption
     *
     * @return Relationship
     */
    public function exemption() {
        return $this->belongsTo(TaxExemption::class, 'exemption_id');
    }

    /**
     * Tax Category
     *
     * @return Relationship
     */
    public function tax_category() {
        return $this->belongsTo(TaxType::class, 'tax_id');
    }

    public function invoice_item()
    {
        return $this->morphOne(InvoiceItem::class, 'itemable');
    }

    public function quote_item()
    {
        return $this->morphOne(QuoteItem::class, 'itemable');
    }

    public function credit_note_item()
    {
        return $this->morphOne(CreditNoteItem::class, 'itemable');
    }
}
