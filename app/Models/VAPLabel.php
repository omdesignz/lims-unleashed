<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class VAPLabel extends Model
{
    use HasFactory;

    protected $table = 'labels';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'content',
        'width',
        'height',
        'background_color',
        'text_color',
        'font_size',
        'logo_path',
        'border_width',
        'border_color',
        'template_data',
        'is_active',
        'lab_id',
        'department_id',
        'user_id',
        'text_position',
        'logo_position',
        'text_alignment',
        'logo_size',
        'has_qr_code',
        'qr_code_content',
        'qr_code_position',
        'qr_code_size',
        'has_barcode',
        'barcode_content',
        'barcode_type',
        'barcode_position',
        'barcode_width',
        'barcode_height',
    ];

    protected $casts = [
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'template_data' => 'array',
        'text_position' => 'array',
        'logo_position' => 'array',
        'qr_code_position' => 'array',
        'barcode_position' => 'array',
        'is_active' => 'boolean',
        'has_qr_code' => 'boolean',
        'has_barcode' => 'boolean',
        'logo_size' => 'decimal:2',
        'qr_code_size' => 'decimal:2',
        'barcode_width' => 'decimal:2',
        'barcode_height' => 'decimal:2',
        'font_size' => 'integer',
        'border_width' => 'integer',
    ];

    protected $attributes = [
        'type' => 'custom',
        'background_color' => '#ffffff',
        'text_color' => '#000000',
        'font_size' => 12,
        'border_width' => 1,
        'border_color' => '#000000',
        'is_active' => true,
        'text_alignment' => 'center',
        'has_qr_code' => false,
        'has_barcode' => false,
        'barcode_type' => 'CODE128',
    ];

    public function lab(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class, 'lab_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function dimensions(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'width' => $this->width,
                'height' => $this->height,
                'unit' => 'mm'
            ]
        );
    }
}