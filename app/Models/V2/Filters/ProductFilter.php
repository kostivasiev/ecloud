<?php

namespace App\Models\V2\Filters;

use UKFast\Sieve\Filters\StringFilter;
use UKFast\Sieve\ModifiesQueries;
use UKFast\Sieve\SearchTerm;
use UKFast\Sieve\WrapsFilter;

class ProductFilter extends StringFilter implements WrapsFilter
{
    public function modifyQuery($query, SearchTerm $search)
    {
        if ($search->operator() == 'eq') {
            $query->where('product_name', 'LIKE', 'az-%: ' . str_replace('_', ' ', $search->term()) . '%');
        }
        if ($search->operator() == 'neq') {
            $query->where('product_name', 'NOT LIKE', 'az-%: ' . str_replace('_', ' ', $search->term()) . '%');
        }
        if ($search->operator() == 'in') {
            $terms = explode(',', $search->term());
            $query->where('product_name', 'LIKE', 'az-%: ' . str_replace('_', ' ', array_pop($terms)) . '%');
            foreach ($terms as $term) {
                $query->orWhere('product_name', 'LIKE', 'az-%: ' . str_replace('_', ' ', $term) . '%');
            }
        }
        if ($search->operator() == 'nin') {
            $terms = explode(',', $search->term());
            $query->where('product_name', 'NOT LIKE', 'az-%: ' . str_replace('_', ' ', array_pop($terms)) . '%');
            foreach ($terms as $term) {
                $query->orWhere('product_name', 'NOT LIKE', 'az-%: ' . str_replace('_', ' ', $term) . '%');
            }
        }
        if ($search->operator() == 'lk') {
            $query->where('product_name', 'LIKE', 'az-%: ' . str_replace('_', ' ', $this->prepareLike($search->term())) . '%');
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