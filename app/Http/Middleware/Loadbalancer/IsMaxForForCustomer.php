<?php

namespace App\Http\Middleware\Loadbalancer;

use App\Http\Middleware\ResellerBypass;
use App\Models\V2\LoadBalancer;
use App\Models\V2\LoadBalancerSpecification;
use Closure;
use Illuminate\Support\Facades\Auth;

class IsMaxForForCustomer
{
    use ResellerBypass;

    public function handle($request, Closure $next)
    {
        if ($this->resellerBypass()) {
            return $next($request);
        }

        $limit = config('load-balancer.customer_max_per_az');

        $nodes = 0;
        LoadBalancer::forUser(Auth::user())
            ->where('availability_zone_id', $request->input('availability_zone_id'))
            ->each(function ($loadBalancer) use (&$nodes) {
                $nodes = $nodes + $loadBalancer->loadBalancerNodes->count();
            });

        $loadBalancerSpec = LoadBalancerSpecification::findOrFail($request->input('load_balancer_spec_id'));

        if ($nodes + $loadBalancerSpec->node_count > $limit) {
            return response()->json([
                'title' => 'Forbidden',
                'detail' => 'A maximum of ' . $limit . ' load balancer nodes can be launched per availability zone.',
                'status' => 403,
            ], 403);
        }

        return $next($request);
    }
}
