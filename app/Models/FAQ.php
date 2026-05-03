<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class FAQ extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = 'faqs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'description',
        'category_id',
        'extra_data',
    ];

    protected $table = 'faqs';
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
     * FAQ Category
     *
     * @return Relationship
     */
    public function category()
    {
        return $this->belongsTo(FAQCategory::class, 'category_id');
    }

    /**
     * FAQ Answers
     *
     * @return Relationship
     */
    public function answers()
    {
        return $this->hasMany(FAQAnswer::class, 'faq_id');
    }
}
