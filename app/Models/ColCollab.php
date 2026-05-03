<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ColCollab extends Model
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
        'collaboration_id', 
    ];

    protected $table = 'col_collab';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];


    public function collection()
    {
      return $this->belongsTo(Collection::class, 'collection_id');
    }


    public function collaboration()
    {
      return $this->belongsTo(CollectionCollaboration::class, 'collaboration_id');
    }
}
