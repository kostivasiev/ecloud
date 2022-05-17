<?php

namespace App\Models\V2\Filters;

use App\Models\V2\VpcAble;
use UKFast\Sieve\Filters\StringFilter;
use UKFast\Sieve\ModifiesQueries;
use UKFast\Sieve\SearchTerm;
use UKFast\Sieve\WrapsFilter;

class VpcIdFilter extends StringFilter implements WrapsFilter
{

    protected $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function modifyQuery($query, SearchTerm $search)
    {
        if ($this->model instanceof VpcAble) {
            $modelIds = $this->model::forUser(request()->user())->get();

            if ($search->operator() == 'eq') {
                $modelIds = $modelIds->reject(function ($model) use ($search) {
                    return !$model->vpnService || $model->vpnService->router->vpc->id != $search->term();
                });
            }

            if ($search->operator() == 'neq') {
                $modelIds = $modelIds->reject(function ($model) use ($search) {
                    return !$model->vpnService || $model->vpnService->router->vpc->id == $search->term();
                });
            }

            if ($search->operator() == 'lk') {
                $modelIds = $modelIds->reject(function ($model) use ($search) {
                    return !$model->vpnService
                        || preg_match(
                            '/' . str_replace('\*', '\S*', preg_quote($search->term())) . '/',
                            $model->vpnService->router->vpc->id
                        ) === 0;
                });
            }

            if ($search->operator() == 'nlk') {
                $modelIds = $modelIds->reject(function ($model) use ($search) {
                    return !$model->vpnService
                        || preg_match(
                            '/' . str_replace('\*', '\S*', preg_quote($search->term())) . '/',
                            $model->vpnService->router->vpc->id
                        ) === 1;
                });
            }

            if ($search->operator() == 'in') {
                $ids = explode(',', $search->term());
                $modelIds = $modelIds->reject(function ($model) use ($ids) {
                    return !$model->vpnService || !in_array($model->vpnService->router->vpc->id, $ids);
                });
            }

            if ($search->operator() == 'nin') {
                $ids = explode(',', $search->term());
                $modelIds = $modelIds->reject(function ($model) use ($ids) {
                    return !$model->vpnService || in_array($model->vpnService->router->vpc->id, $ids);
                });
            }

            $query->whereIn('id', $modelIds->map(function ($model) {
                return $model->id;
            }));
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

    public function getWrapped(): ModifiesQueries
    {
        return $this->filter;
    }
}
