<?php

namespace App\Models\V2\Filters\VpnEndpoint;

use App\Models\V2\VpnEndpoint;
use UKFast\Sieve\Filters\StringFilter;
use UKFast\Sieve\ModifiesQueries;
use UKFast\Sieve\SearchTerm;
use UKFast\Sieve\WrapsFilter;

class VpcIdFilter extends StringFilter implements WrapsFilter
{

    public function modifyQuery($query, SearchTerm $search)
    {
        $vpnEndpointIds = VpnEndpoint::forUser(request()->user())->get();

        if ($search->operator() == 'eq') {
            $vpnEndpointIds = $vpnEndpointIds->reject(function ($vpnEndpoint) use ($search) {
                return !$vpnEndpoint->vpnService || $vpnEndpoint->vpnService->router->vpc->id != $search->term();
            });
        }

        if ($search->operator() == 'neq') {
            $vpnEndpointIds = $vpnEndpointIds->reject(function ($vpnEndpoint) use ($search) {
                return !$vpnEndpoint->vpnService || $vpnEndpoint->vpnService->router->vpc->id == $search->term();
            });
        }

        if ($search->operator() == 'lk') {
            $vpnEndpointIds = $vpnEndpointIds->reject(function ($vpnEndpoint) use ($search) {
                return !$vpnEndpoint->vpnService
                    || preg_match(
                        '/' . str_replace('\*', '\S*', preg_quote($search->term())) . '/',
                        $vpnEndpoint->vpnService->router->vpc->id
                    ) === 0;
            });
        }

        if ($search->operator() == 'nlk') {
            $vpnEndpointIds = $vpnEndpointIds->reject(function ($vpnEndpoint) use ($search) {
                return !$vpnEndpoint->vpnService
                    || preg_match(
                        '/' . str_replace('\*', '\S*', preg_quote($search->term())) . '/',
                        $vpnEndpoint->vpnService->router->vpc->id
                    ) === 1;
            });
        }

        if ($search->operator() == 'in') {
            $ids = explode(',', $search->term());
            $vpnEndpointIds = $vpnEndpointIds->reject(function ($vpnEndpoint) use ($ids) {
                return !$vpnEndpoint->vpnService || !in_array($vpnEndpoint->vpnService->router->vpc->id, $ids);
            });
        }

        if ($search->operator() == 'nin') {
            $ids = explode(',', $search->term());
            $vpnEndpointIds = $vpnEndpointIds->reject(function ($vpnEndpoint) use ($ids) {
                return !$vpnEndpoint->vpnService || in_array($vpnEndpoint->vpnService->router->vpc->id, $ids);
            });
        }

        $query->whereIn('id', $vpnEndpointIds->map(function ($vpnEndpoint) {
            return $vpnEndpoint->id;
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
