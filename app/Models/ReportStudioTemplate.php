<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportStudioTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\ReportStudioTemplateFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'studio_type',
        'renderer',
        'status',
        'is_default',
        'theme_preset',
        'canva_design_url',
        'description',
        'layout_schema',
        'export_settings',
        'created_by_id',
        'updated_by_id',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'layout_schema' => 'array',
            'export_settings' => 'array',
        ];
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForStudio($query, string $studioType)
    {
        return $query->where('studio_type', $studioType);
    }

    public static function resolveDefaultFor(string $studioType): ?self
    {
        return self::query()
            ->forStudio($studioType)
            ->active()
            ->orderByDesc('is_default')
            ->latest('updated_at')
            ->first();
    }
}
