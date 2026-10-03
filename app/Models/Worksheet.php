<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use LogicException;

class Worksheet extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = 'worksheets';

    /** @var list<string> */
    protected $fillable = ['name', 'worksheets', 'user_id', 'lab_id', 'analysis_id'];

    protected function casts(): array
    {
        return ['worksheets' => 'array', 'lab_id' => 'integer', 'analysis_id' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $worksheet): void {
            if ($worksheet->isDirty(['lab_id', 'analysis_id', 'user_id'])
                || Arr::except($worksheet->getOriginal('worksheets') ?? [], ['sheets'])
                    !== Arr::except($worksheet->worksheets ?? [], ['sheets'])) {
                throw new LogicException('Worksheet ownership, author, analytical lineage and issued scope cannot be changed.');
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class);
    }

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }
}
