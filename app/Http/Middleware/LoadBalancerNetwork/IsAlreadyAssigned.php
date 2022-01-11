<?php
namespace App\Http\Middleware\LoadBalancerNetwork;

use App\Models\V2\FloatingIp;
use App\Models\V2\LoadBalancer;
use Closure;
use Symfony\Component\HttpFoundation\Response;

class IsAlreadyAssigned
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $loadBalancer = LoadBalancer::forUser($request->user())->findOrFail($request->route('loadBalancerNetworkId'));

        if (!empty($floatingIp->resource_id)) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Conflict',
                        'detail' => "The Floating IP is already assigned to a resource.",
                        'status' => Response::HTTP_CONFLICT,
                    ]
                ]
            ], Response::HTTP_CONFLICT);
        }

        return $next($request);
    }
}
