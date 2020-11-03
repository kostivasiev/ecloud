<?php

namespace App\Http\Middleware\V2;

use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\LoadBalancerCluster;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\Volume;
use Closure;
use Illuminate\Http\JsonResponse;

/**
 * Class HasResources
 * @package App\Http\Middleware
 *
 * Ensure a VPC can not be deleted if it has associated resources
 */
class HasResources
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $vpcId = $request->route('vpcId');

        if (array_sum([
            Instance::forVpc($vpcId)->count(),
            FloatingIp::forVpc($vpcId)->count(),
            Network::forVpc($vpcId)->count(),
            Router::forVpc($vpcId)->count(),
            Volume::forVpc($vpcId)->count(),
            LoadBalancerCluster::forVpc($vpcId)->count(),
        ]) > 0) {
            return JsonResponse::create([
                'errors' => [
                    'title' => 'Conflict',
                    'detail' => 'Unable to delete VPC with active resources',
                    'status' => 409,
                    'source' => 'vpc'
                ]
            ], 409);
        }

        return $next($request);
    }
}
