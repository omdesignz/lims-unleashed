<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class GlobalFilter implements Filter
{
    protected array $searchableFields;

    public function __construct(array $searchableFields)
    {
        $this->searchableFields = $searchableFields;
    }

    public function __invoke(Builder $query, $value, string $property)
    {

        if (!empty($value)) {
            $query->where(function (Builder $query) use ($value) {
                foreach ($this->searchableFields as $field) {
                    $query->orWhere($field, 'like', '%' . $value . '%');
                }
            });
        }
    }
}