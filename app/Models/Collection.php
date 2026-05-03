<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Collection extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'collectionable_id',
        'collectionable_type',
        'customer_id',
        'warehouse_id',
        'processed',
        'recollection',
    ];

    protected $table = 'collections';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'processed' => 'boolean',
        'recollection' => 'boolean',
    ];


    /**
     * Collaborations
     *
     * @return Relationship
     */
    public function collaborations()
    {
        return $this->belongsToMany(CollectionCollaboration::class, 'col_collab', 'collection_id', 'collaboration_id');
    }

    /**
     * Reasons
     *
     * @return Relationship
     */
    public function reasons()
    {
        return $this->belongsToMany(CollectionReason::class, 'col_reason', 'collection_id', 'reason_id');
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

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }



    public function collectionable()
    {
      return $this->morphTo();
    }

    /**
     * Invoice
     *
     * @return Relationship
     */
    public function invoice()
    {
        return $this->morphOne(Invoice::class, 'invoiceable')->withTrashed();
    }

    
    public function products()
    {
        return $this->hasMany(CollectionProduct::class, 'collection_id');
    }

    public function certificates()
    {
        return $this->hasManyThrough(QualityCertificate::class, CollectionProduct::class, 'collection_id', 'collection_id', 'id'); 
    }


    public static function boot() {
        parent::boot();
        self::deleting(function($model) { // before delete() method call this
             $model->collectionable->delete();
        });
    }

}
