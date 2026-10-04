<?php

namespace App\Models;

use Database\Factories\CustomChartFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A chart a person saved on their analytics board, for one laboratory.
 *
 * @property array<string, string>|null $colors series or category name => hex colour
 */
class CustomChart extends Model
{
    /** @use HasFactory<CustomChartFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['title', 'dataset', 'measure', 'dimension', 'split', 'kind', 'period', 'colors', 'position'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'colors' => 'array',
            'position' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class, 'lab_id');
    }

    /**
     * @param  Builder<CustomChart>  $query
     * @return Builder<CustomChart>
     */
    public function scopeOnBoard(Builder $query, User $user, int $labId): Builder
    {
        return $query->where('user_id', $user->id)->where('lab_id', $labId)->orderBy('position')->orderBy('id');
    }
}
