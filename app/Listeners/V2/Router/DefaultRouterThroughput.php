<?php

namespace App\Listeners\V2\Router;

use App\Events\V2\Router\Creating;
use App\Models\V2\Router;
use Illuminate\Support\Facades\Log;

class DefaultRouterThroughput
{
    public function handle(Creating $event)
    {
        /** @var Router $router */
        $router = $event->model;

        if (!empty($router->router_throughput_id)) {
            return;
        }

        // Default router throughput is 20Mbps
        $committedBandwidth = ($router->is_management) ?
            config('router.throughput.admin_default.bandwidth'):
            config('router.throughput.default.bandwidth');
        $routerThroughput = $router->availabilityZone->routerThroughputs
            ->where('committed_bandwidth', $committedBandwidth)
            ->first();

        if (empty($routerThroughput)) {
            Log::error('Failed to set default router throughput for router ' . $router->id
                . ', the default valued router throughput record for availability zone ' . $router->availabilityZone->id . ' was not found');
            return;
        }

        $router->router_throughput_id = $routerThroughput->id;

        Log::info(get_class($this) . ' : Throughput was not specified whilst creating router, set to default for availability zone ' . $router->availabilityZone->id . ': ' . $routerThroughput->id . ' (' . $routerThroughput->name . ')');
    }
}
