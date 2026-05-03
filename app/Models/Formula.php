<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Formula extends Model
{
    use HasFactory;

    public const MENU_NAME = 'formulas';

    // protected $guarded = [];

    protected $fillable = [
        'name', 'code', 'expression', 'formula_expression', 'description', 'variables', 'category', 'output_unit', 'decimal_places', 'is_active', 'created_by'
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean'
    ];

    // public function variables(): HasMany
    // {
    //     return $this->hasMany(Variable::class);
    // }

    public function parameters()
    {
        return $this->hasMany(Parameter::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getVariableLabels(): array
    {
        return collect($this->variables ?? [])
            ->pluck('label', 'name')
            ->toArray();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function isUsed(): bool
    {
        return $this->parameters()->exists();
    }
}
