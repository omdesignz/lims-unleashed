<?php

namespace App\Models;

use Database\Factories\MaintenanceTaskImportFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class MaintenanceTaskImport extends Model
{
    /** @use HasFactory<MaintenanceTaskImportFactory> */
    use HasFactory, HasUuids;

    protected $fillable = ['id', 'lab_id', 'user_id', 'file_hash', 'row_count', 'task_ids'];

    protected function casts(): array
    {
        return ['row_count' => 'integer', 'task_ids' => 'array'];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Completed maintenance import receipts are immutable.');
        });
        static::deleting(function (): never {
            throw new LogicException('Completed maintenance import receipts must be retained.');
        });
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class, 'lab_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
