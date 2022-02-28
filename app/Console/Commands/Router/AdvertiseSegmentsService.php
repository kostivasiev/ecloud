<?php

namespace App\Console\Commands\Router;

use App\Models\V2\Router;
use Illuminate\Console\Command;

class AdvertiseSegmentsService extends Command
{
    protected $signature = 'router:advertise-segments-service {--D|debug} {--T|test-run}';
    protected $description = 'Sets existing Admin T1s to advertise their connected segments and services';

    public function handle()
    {
        Router::isManagement()->each(function (Router $router) {
            // 1. Is router advertising it's segments/services and connected to T0?
            $advertisedTypes = $this->getAdvertisedTypes($router);
            if (!$advertisedTypes) {
                $this->error($router->id . ' : route_advertisement_types not found');
                return;
            }

            if (is_array($advertisedTypes) && in_array('TIER1_CONNECTED', $advertisedTypes)) {
                $this->info($router->id . ' : already contains TIER1_CONNECTED type.');
                return;
            }

            // 2. If not, then make the change
            $advertisedTypes[] = 'TIER1_CONNECTED';
            $response = $this->updateRouteAdvertisementTypes($router, $advertisedTypes);
            if (!$response) {
                $this->error($router->id . ' : Failed to update route_advertisement_types');
                return;
            }

            $this->info($router->id . ' : Updated router_advertisement_types');
        });
    }

    public function getAdvertisedTypes(Router $router)
    {
        if ($router->availabilityZone()->count() === 0) {
            $this->error($router->id . ' : Availability Zone `' . $router->availability_zone_id . '` not found');
            return false;
        }
        try {
            $response = $router->availabilityZone->nsxService()->get(
                'policy/api/v1/infra/tier-1s/' . $router->id
            );
        } catch (\Exception $e) {
            $this->error($router->id . ' : ' . $e->getMessage());
            return false;
        }
        $response = json_decode($response->getBody()->getContents());
        if ($response->id != $router->id) {
            $this->error($router->id . ' : No results found.');
            return false;
        }

        // now check that the t1 is connected to an admin t0
        if ($this->checkT0Connection($router, $response)) {
            return false;
        }

        return $response->route_advertisement_types;
    }

    public function checkT0Connection(Router $router, $response): bool
    {
        if ($router->vpc()->count() === 0) {
            $this->error($router->id . ' : VPC `' . $router->vpc_id . '` not found');
            return false;
        }
        $tier0Tag = ($router->vpc->advanced_networking) ?
            config('defaults.tag.networking.management.advanced'):
            config('defaults.tag.networking.management.default');

        try {
            $tier0SearchResponse = $router->availabilityZone->nsxService()->get(
                '/policy/api/v1/search/query?query=resource_type:Tier0%20AND%20'.
                'tags.scope:ukfast%20AND%20tags.tag:' . $tier0Tag
            );
        } catch (\Exception $e) {
            $this->error($router->id . ' : ' . $e->getMessage());
            return false;
        }
        $tier0SearchResponse = json_decode($tier0SearchResponse->getBody()->getContents());

        if ($tier0SearchResponse->result_count != 1) {
            $this->error($router->id . ' : No tagged T0 could be found');
            return false;
        }

        if ($response->tier0_path != $tier0SearchResponse->results[0]->path) {
            $this->info($router->id . ' : is not connected to the correct T0 path');
            return false;
        }

        $this->info($router->id . ' : is connected to the correct T0 path');
        return true;
    }

    public function updateRouteAdvertisementTypes(Router $router, array $types): bool
    {
        $this->info('Updating ' . $router->id . ', adding TIER1_CONNECTED type');

        if (!$this->option('test-run')) {
            try {
                $response = $router->availabilityZone->nsxService()->patch('policy/api/v1/infra/tier-1s/' . $router->id, [
                    'json' => [
                        'route_advertisement_types' => $types
                    ],
                ]);
            } catch (\Exception $e) {
                $this->error($router->id . ' : ' . $e->getMessage());
                return false;
            }
            return ($response->getStatusCode() === 200);
        }

        return true;
    }
}
