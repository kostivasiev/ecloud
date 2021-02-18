<?php

namespace App\Listeners\V2\Router;

use App\Events\V2\Router\Creating;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DefaultRouterThroughput
{
    public function handle(Creating $event)
    {
        /** @var Router $router */
        $router = $event->model;

        if (empty($router->router_throughput_id)) {
            if (empty($router->availability_zone_id)) {
                $availabilityZone = Vpc::forUser(Auth::user())
                    ->findOrFail($router->vpc_id)
                    ->region
                    ->availabilityZones
                    ->first();
            } else {
                $availabilityZone = $router->availabilityZone;
            }

            if (empty($availabilityZone)) {
                Log::error('Failed to set default router throughput for router ' . $router->id
                    . ', unable to determine the router\s availability zone');
                return;
            }

            // Default router throughput is 20Mbps
            $routerThroughput = $availabilityZone->routerThroughputs
                ->where('committed_bandwidth', config('router.throughput.default.bandwidth'))
                ->first();

            if (empty($routerThroughput)) {
                Log::error('Failed to set default router throughput for router ' . $router->id
                    . ', the default valued router throughput record for availability zone ' . $availabilityZone->id . ' was not found');
                return;
            }

            $router->router_throughput_id = $routerThroughput->id;

            Log::info(get_class($this) . ' : Throughput was not specified whilst creating router, set to default for availability zone ' . $availabilityZone->id . ': ' . $routerThroughput->id . ' (' . $routerThroughput->name . ')');
        }
    }
}
