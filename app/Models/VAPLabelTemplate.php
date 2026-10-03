<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class VAPLabelTemplate extends Model
{
    use HasFactory;

    public const PRESENTATION_FIELDS = [
        'type', 'content', 'width', 'height', 'background_color', 'text_color',
        'font_size', 'border_width', 'border_color', 'text_alignment',
        'has_qr_code', 'qr_code_content', 'qr_code_size', 'has_barcode',
        'barcode_content', 'barcode_type', 'barcode_width', 'barcode_height',
        'logo_path', 'logo_size',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $template): void {
            if (($template->is_system && $template->lab_id !== null)
                || (! $template->is_system && $template->lab_id === null)) {
                throw new LogicException('Custom templates require a laboratory; system presets must remain shared.');
            }
        });

        static::updating(function (self $template): void {
            if ($template->isDirty(['lab_id', 'is_system'])) {
                throw new LogicException('Label template ownership cannot be reassigned.');
            }
        });
    }

    protected $table = 'label_templates';

    protected $fillable = [
        'name',
        'description',
        'category',
        'preview_image',
        'template_data',
        'is_active',
        'is_featured',
        'lab_id',
    ];

    protected $casts = [
        'template_data' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_system' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_featured' => false,
    ];
}
