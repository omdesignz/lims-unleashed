<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Number;


class CollectionCollaboration extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'collaboration_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    protected $table = 'collection_collaborations';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

}
