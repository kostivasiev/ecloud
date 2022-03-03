<?php

namespace App\Console\Commands\Router;

use App\Models\V2\Router;
use Illuminate\Console\Command;

class FixT0Values extends Command
{
    protected $signature = 'router:fix-t0-settings {--D|debug} {--T|test-run} {--router=}';

    protected $description = 'Fixes the Admin Router T0 values';

    public function handle()
    {
        $routers = ($this->option('router')) ?
            Router::isManagement()->where('id', '=', $this->option('router'))->get():
            Router::isManagement()->get();
        $routers->each(function ($router) {
            $this->info('---');
            $this->info('Processing router ' . $router->id . ' (' . $router->name . ')');
            $this->info('---');

            // 1. Get the tag
            $tier0Tag = $this->getT0Tag($router);
            if (!$tier0Tag) {
                $this->error($router->id . ' : skipped, tier0Tag could not be determined.');
                return;
            }

            // 2. Get the tier0_path using the tag
            $tagPath = $this->getTier0TagPath($router, $tier0Tag);
            if (!$tagPath) {
                $this->error($router->id . ' : skipped, tag `' . $tier0Tag . '` not found.');
                return;
            }

            // 3. Get the tier0_path of the router
            $tier0Path = $this->getTier0Config($router);
            if (!$tier0Path) {
                $this->error($router->id . ' : skipped, nsx config not found.');
                return;
            }

            // 4. If the values are different, patch the tier0_path using the value from the tag
            if ($tagPath !== $tier0Path) {
                $this->info("Current T0 path $tier0Path not equal expected $tagPath");
                $result = $this->updateTier0Config($router, $tagPath);
                if (!$result) {
                    $this->error($router->id . ' : tier0_path failed modification.');
                    return;
                }
                $this->info($router->id . ' : tier0_path successfully modified.');
                return;
            }

            // 5. If the values are the same do nothing
            $this->info($router->id . ' : skipped, tier0_path is correct.');
        });
    }

    public function getT0Tag(Router $router)
    {
        if ($router->vpc()->count() === 0) {
            $this->error($router->id . ' : VPC `' . $router->vpc_id . '` not found');
            return false;
        }
        return ($router->vpc->advanced_networking) ?
            config('defaults.tag.networking.management.advanced') :
            config('defaults.tag.networking.management.default');
    }

    public function getTier0TagPath(Router $router, string $tier0Tag)
    {
        if ($router->availabilityZone()->count() === 0) {
            $this->error($router->id . ' : Availability Zone `' . $router->availability_zone_id . '` not found');
            return false;
        }
        try {
            $response = $router->availabilityZone->nsxService()->get(
                '/policy/api/v1/search/query?query=resource_type:Tier0%20AND%20tags.scope:ukfast%20AND%20tags.tag:' . $tier0Tag
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

        return $response->results[0]->path;
    }

    public function getTier0Config(Router $router)
    {
        try {
            $response = $router->availabilityZone->nsxService()->get('policy/api/v1/infra/tier-1s/' . $router->id);
        } catch (\Exception $e) {
            $this->error($router->id . ' : ' . $e->getMessage());
            return false;
        }
        $response = json_decode($response->getBody()->getContents());
        if ($response->id != $router->id) {
            $this->error($router->id . ' : No results found.');
            return false;
        }
        return $response->tier0_path;
    }

    public function updateTier0Config($router, $correctPath): bool
    {
        $this->info('Updating router ' . $router->id . '(' . $router->name . ')');
        if (!$this->option('test-run')) {
            try {
                $response = $router->availabilityZone->nsxService()->patch(
                    'policy/api/v1/infra/tier-1s/' . $router->id,
                    [
                        'json' => [
                            'tier0_path' => $correctPath
                        ],
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
