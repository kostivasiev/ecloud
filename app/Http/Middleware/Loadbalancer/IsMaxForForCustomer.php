<?php

namespace App\Http\Middleware\Loadbalancer;

use App\Http\Middleware\ResellerBypass;
use App\Models\V2\Instance;
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

        $nodes = LoadBalancer::forUser(Auth::user())
            ->where('availability_zone_id', $request->input('availability_zone_id'))
            ->get()->pluck('nodes')->sum();

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
