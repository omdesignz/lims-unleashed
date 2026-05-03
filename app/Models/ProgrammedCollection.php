<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgrammedCollection extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'programmed_collections';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'quote_id', 
        'collection_location', 
        'entry_date',
        'vehicle_reference', 
        'vehicle_id', 
        'col_date',
        'entry_date',
        'status', 
        'placed_analysis', 
        'quoted',
    ];

    protected $table = 'programmed_collections';
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'col_date'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'placed_analysis' => 'boolean',
        'quoted' => 'boolean'
    ];


    public function collection()
    {
        return $this->morphOne(Collection::class, 'collectionable');
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function quote()
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }
}
