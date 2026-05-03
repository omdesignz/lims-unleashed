<?php

namespace App\Models;

use HighSolutions\EloquentSequence\Sequence;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractGuide extends Model
{
    use HasFactory, SoftDeletes, Sequence;

    public const MENU_NAME = 'contract_guides';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'customer_id',
        'warehouse_id',
        'guide_no',
        'ref_no',
        'entry_point',
        'collection_point',
        'guide_month',
        'du_no',
        'nif',
        'contact',
        'email',
        'bl',
        'seq',
        'collection_id',
        'date',
        'file_path',
        'obs',
        'extra_data',
    ];

    protected $table = 'contract_guides';
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'date'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'extra_data' => AsCollection::class
    ];


    public function sequence()
    {
        return [
            'group' => 'guide_month',
            'fieldName' => 'seq',
        ];
    }

    /**
     * Export Certificate Items
     *
     * @return Relationship
     */
    public function items()
    {
        return $this->hasMany(ContractGuideItem::class, 'guide_id');
    }

    /**
     * Customer
     *
     * @return Relationship
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * User
     *
     * @return Relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Warehouse
     *
     * @return Relationship
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Lab Code
     *
     * @return Relationship
     */
    public function collection()
    {
        return $this->belongsTo(LabCode::class, 'collection_id');
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($guide) {

            $guide->guide_no = 'GC ' . $guide->guide_month . '/' . str_pad($guide->seq, 4, '0', STR_PAD_LEFT);
            $guide->save();
        });
    }
}
