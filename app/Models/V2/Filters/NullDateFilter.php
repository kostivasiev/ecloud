<?php

namespace App\Models\V2\Filters;

use UKFast\Sieve\Filters\StringFilter;
use UKFast\Sieve\ModifiesQueries;
use UKFast\Sieve\SearchTerm;
use UKFast\Sieve\WrapsFilter;

class NullDateFilter extends StringFilter implements WrapsFilter
{
    public function modifyQuery($query, SearchTerm $search)
    {
        if ($search->term() == 'null') {
            if ($search->operator() == 'eq') {
                $query->whereNull($search->column());
            }
            if ($search->operator() == 'neq') {
                $query->whereNotNull($search->column());
            }
        }
    }

    public function wrap(ModifiesQueries $filter)
    {
        $this->filter = $filter;
    }

    public function operators()
    {
        return $this->filter->operators();
    }
}
