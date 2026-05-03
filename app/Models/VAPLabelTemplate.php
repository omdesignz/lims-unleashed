<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VAPLabelTemplate extends Model
{
    use HasFactory;

    protected $table = 'label_templates';

    protected $fillable = [
        'name',
        'description',
        'category',
        'preview_image',
        'template_data',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'template_data' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_featured' => false,
    ];
}