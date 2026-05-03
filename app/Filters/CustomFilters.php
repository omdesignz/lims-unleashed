<?php

namespace App\Filters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class CustomFilters implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        if (str_starts_with($value, 'begins_with(')) {
            $value = str_replace(['begins_with(', ')'], '', $value);
            $query->where($property, 'like', "$value%");
        } elseif (str_starts_with($value, 'ends_with(')) {
            $value = str_replace(['ends_with(', ')'], '', $value);
            $query->where($property, 'like', "%$value");
        } elseif (str_starts_with($value, 'different_than(')) {
            $value = str_replace(['different_than(', ')'], '', $value);
            $query->where($property, '!=', $value);
        } else {
            $query->where($property, $value);
        }
    }
}