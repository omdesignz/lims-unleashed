<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class FAQAnswer extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = 'faq_answers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'description',
        'faq_id',
        'extra_data',
    ];

    protected $table = 'faq_answers';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'extra_data' => AsCollection::class,
    ];

    /**
     * FAQ
     *
     * @return Relationship
     */
    public function faq()
    {
        return $this->belongsTo(FAQ::class, 'faq_id');
    }
}
