<?php

namespace App\Http\Middleware\Vips;

use App\Models\V2\LoadBalancerNetwork;
use Closure;

class MaxVipLimitReached
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $limit = config('load-balancer.limits.vips-max');
        $loadBalancerNetwork = LoadBalancerNetwork::find($request->input('load_balancer_network_id'));
        if ($loadBalancerNetwork) {
            if ($loadBalancerNetwork->loadBalancer->getVipsTotal() >= $limit) {
                return response()->json([
                    'title' => 'Forbidden',
                    'detail' => 'A maximum of ' . $limit . ' vips can be assigned per load balancer.',
                    'status' => 403,
                ], 403);
            }
        }

        return $next($request);
    }
}
