<?php

namespace App\Models\V2\Filters\VpnService;

use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use UKFast\Sieve\Filters\StringFilter;
use UKFast\Sieve\ModifiesQueries;
use UKFast\Sieve\SearchTerm;
use UKFast\Sieve\WrapsFilter;

class VpcIdFilter extends StringFilter implements WrapsFilter
{

    public function modifyQuery($query, SearchTerm $search)
    {
        $vpnServiceIds = VpnService::forUser(request()->user())->get();

        if ($search->operator() == 'eq') {
            $vpnServiceIds = $vpnServiceIds->reject(function ($vpnService) use ($search) {
                return !$vpnService->router || $vpnService->router->vpc_id != $search->term();
            });
        }

        if ($search->operator() == 'neq') {
            $vpnServiceIds = $vpnServiceIds->reject(function ($vpnService) use ($search) {
                return !$vpnService->router || $vpnService->router->vpc_id == $search->term();
            });
        }

        if ($search->operator() == 'lk') {
            $vpnServiceIds = $vpnServiceIds->reject(function ($vpnService) use ($search) {
                return !$vpnService->router
                    || preg_match(
                        '/' . str_replace('\*', '\S*', preg_quote($search->term())) . '/',
                        $vpnService->router->vpc_id
                    ) === 0;
            });
        }

        if ($search->operator() == 'nlk') {
            $vpnServiceIds = $vpnServiceIds->reject(function ($vpnService) use ($search) {
                return !$vpnService->router
                    || preg_match(
                        '/' . str_replace('\*', '\S*', preg_quote($search->term())) . '/',
                        $vpnService->router->vpc_id
                    ) === 1;
            });
        }

        if ($search->operator() == 'in') {
            $ids = explode(',', $search->term());
            $vpnServiceIds = $vpnServiceIds->reject(function ($vpnService) use ($ids) {
                return !$vpnService->router || !in_array($vpnService->router->vpc_id, $ids);
            });
        }

        if ($search->operator() == 'nin') {
            $ids = explode(',', $search->term());
            $vpnServiceIds = $vpnServiceIds->reject(function ($vpnService) use ($ids) {
                return !$vpnService->router || in_array($vpnService->router->vpc_id, $ids);
            });
        }

        $query->whereIn('id', $vpnServiceIds->map(function ($vpnService) {
            return $vpnService->id;
        }));
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
