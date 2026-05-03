<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ColReason extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'collection_id',
        'reason_id', 
    ];

    protected $table = 'col_reason';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];


    public function collection()
    {
      return $this->belongsTo(Collection::class, 'collection_id');
    }


    public function reason()
    {
      return $this->belongsTo(CollectionReason::class, 'reason_id');
    }
}
