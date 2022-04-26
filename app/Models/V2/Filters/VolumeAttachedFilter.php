<?php

namespace App\Models\V2\Filters;

use UKFast\Sieve\Filters\StringFilter;
use UKFast\Sieve\ModifiesQueries;
use UKFast\Sieve\SearchTerm;
use UKFast\Sieve\WrapsFilter;

class VolumeAttachedFilter extends StringFilter implements WrapsFilter
{
    public function modifyQuery($query, SearchTerm $search)
    {
        $operator = '=';
        $count = 0;

        if (($search->operator() == 'eq' && $search->term() == 'true') ||
            ($search->operator() == 'neq' && $search->term() == 'false')) {
            $operator = '>';
        }

        $query->has('instances', $operator, $count);
    }

    public function wrap(ModifiesQueries $filter)
    {
        $this->filter = $filter;
    }

    public function operators()
    {
        return $this->filter->operators();
    }

    public function getWrapped(): ModifiesQueries
    {
        return $this->filter;
    }
}
