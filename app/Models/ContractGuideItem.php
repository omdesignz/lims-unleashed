<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractGuideItem extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'guide_id',
        'product_id',
        'country_id',
        'bl',
        'lot',
        'manufacturer',
        'origin',
        'brand',
        'obs',
        'du_no',
        'collection_id',
        'date',
        'extra_data',
    ];

    protected $table = 'contract_guide_items';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'extra_data' => AsCollection::class
    ];


    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function guide()
    {
        return $this->belongsTo(ContractGuide::class, 'guide_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
