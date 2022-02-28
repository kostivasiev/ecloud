<?php

namespace App\Console\Commands\Router;

use App\Models\V2\Router;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FixEdgeClusters extends Command
{
    protected $signature = 'router:fix-edge-clusters {--D|debug} {--T|test-run} {--router=}';
    protected $description = 'Fixes the Admin Router Edge Cluster values';

    public const EDGE_CLUSTER_PREFIX = '/infra/sites/default/enforcement-points/default/edge-clusters/';
    public const EDGE_CLUSTER_URI = 'policy/api/v1/infra/tier-1s/%s/locale-services/%s';

    public function handle()
    {
        $routers = ($this->option('router')) ?
            Router::where([
                ['id', '=', $this->option('router')],
                ['is_management', '=', true],
            ])->get():
            Router::where('is_management', '=', true)->get();
        $routers->each(function ($router) {
            // 1. Get the correct Edge Cluster ID
            $edgeClusterId = $this->getEdgeClusterUuid($router);
            if (!$edgeClusterId) {
                $this->error($router->id . ' : skipped, Edge Cluster ID could not be determined.');
                return;
            }

            // 2. Check which tag was used
            $existingClusterId = $this->getExistingEdgeClusterUuid($router);
            if (!$existingClusterId) {
                $this->error($router->id . ' : skipped, Existing Edge Cluster ID could not be determined.');
                return;
            }

            // 3. If the ids are different use the new id
            if ($edgeClusterId !== $existingClusterId) {
                $result = $this->updateEdgeClusterId($router, $edgeClusterId);
                if (!$result) {
                    $this->error($router->id . ' : edge_cluster_path failed modification.');
                    return;
                }
                $this->info($router->id . ' : edge_cluster_path successfully modified.');
                return;
            }

            // 4. If the values are the same do nothing
            $this->info($router->id . ' : skipped, edge_cluster_path is correct.');
        });
    }

    public function getT0Tag(Router $router)
    {
        if ($router->vpc()->count() === 0) {
            $this->error($router->id . ' : VPC `' . $router->vpc_id . '` not found');
            return false;
        }
        $tier0Tag = ($router->vpc->advanced_networking) ?
            config('defaults.tag.networking.advanced') :
            config('defaults.tag.networking.default');
        if ($router->is_management) {
            $tier0Tag = ($router->vpc->advanced_networking) ?
                config('defaults.tag.networking.management.advanced') :
                config('defaults.tag.networking.management.default');
        }
        return $tier0Tag;
    }

    public function getEdgeClusterUuid(Router $router)
    {
        if ($router->availabilityZone()->count() === 0) {
            $this->error($router->id . ' : Availability Zone `' . $router->availability_zone_id . '` not found');
            return false;
        }
        $tag = $this->getT0Tag($router);

        try {
            $response = $router->availabilityZone->nsxService()->get(
                'api/v1/search/query?query=resource_type:EdgeCluster' .
                '%20AND%20tags.scope:' . config('defaults.tag.scope') .
                '%20AND%20tags.tag:' . $tag
            );
        } catch (\Exception $e) {
            $this->error($router->id . ' : ' . $e->getMessage());
            return false;
        }
        $response = json_decode($response->getBody()->getContents());

        if ($response->result_count != 1) {
            $this->error($router->id . ' : No results found.');
            return false;
        }

        return $response->results[0]->id;
    }

    public function getExistingEdgeClusterUuid(Router $router)
    {
        try {
            $response = $router->availabilityZone->nsxService()->get(
                sprintf(static::EDGE_CLUSTER_URI, $router->id, $router->id)
            );
        } catch (\Exception $e) {
            $this->error($router->id . ' : ' . $e->getMessage());
            return false;
        }
        $response = json_decode($response->getBody()->getContents());

        return Str::replace(static::EDGE_CLUSTER_PREFIX, '', $response->edge_cluster_path);
    }

    public function updateEdgeClusterId(Router $router, $edgeClusterId): bool
    {
        $this->info('Updating router ' . $router->id . '(' . $router->name . ')');
        if (!$this->option('test-run')) {
            try {
                $response = $router->availabilityZone->nsxService()->patch(
                    sprintf(static::EDGE_CLUSTER_URI, $router->id, $router->id),
                    [
                        'json' => [
                            'edge_cluster_path' => static::EDGE_CLUSTER_PREFIX . $edgeClusterId
                        ]
                    ]
                );
            } catch (\Exception $e) {
                $this->error($router->id . ' : ' . $e->getMessage());
                return false;
            }
            return ($response->getStatusCode() === 200);
        }
        return true;
    }
}
