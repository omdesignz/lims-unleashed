<?php

namespace App\Traits;

use App\Services\ScopedSequenceAllocator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

trait HasScopedSequence
{
    /**
     * @return array{group?: string|array<int, string>, fieldName?: string}
     */
    abstract public function sequence(): array;

    public static function bootHasScopedSequence(): void
    {
        static::creating(function (Model $model): void {
            (new ScopedSequenceAllocator)->assign($model, $model->sequence());
        });

        static::created(function (Model $model): void {
            $configuration = $model->sequence();
            $fields = [
                $configuration['fieldName'] ?? 'seq',
                ...(new ScopedSequenceAllocator)->groups($configuration),
            ];

            $model->syncOriginalAttributes(array_values(array_intersect($fields, array_keys($model->getAttributes()))));
        });

        static::updating(function (Model $model): void {
            $configuration = $model->sequence();
            $fields = [
                $configuration['fieldName'] ?? 'seq',
                ...(new ScopedSequenceAllocator)->groups($configuration),
            ];

            if ($model->isDirty($fields)) {
                throw new LogicException('Issued sequence numbers and their scope cannot be changed. Create a new record instead.');
            }
        });
    }

    public function scopeSequenced(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy($this->sequence()['fieldName'] ?? 'seq', $direction);
    }
}
