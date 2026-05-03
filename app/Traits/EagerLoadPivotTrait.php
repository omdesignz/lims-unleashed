<?php

namespace App\Traits;

trait EagerLoadPivotTrait
{
    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new EagerLoadPivotBuilder($query);
    }
}