<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class SampleByParametersFilter implements Filter
{
    protected array $parameterIDs;

    public function __construct(array $parameterIDs)
    {
        $this->parameterIDs = $parameterIDs;
    }

    public function __invoke(Builder $query, $value, string $property)
    {

        if (!empty($value)) {
            $query->where(function (Builder $query) use ($value) {
                foreach ($this->parameterIDs as $field) {
                    $query->orWhere($field, 'like', '%' . $value . '%');
                }
            });
        }
    }
}