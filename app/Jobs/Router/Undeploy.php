<?php

namespace App\Jobs\Router;

use App\Jobs\TaskJob;
use GuzzleHttp\Exception\ClientException;

class Undeploy extends TaskJob
{
    public function handle()
    {
        $router = $this->task->resource;

        try {
            $router->availabilityZone->nsxService()->get('policy/api/v1/infra/tier-1s/' . $router->id);
        } catch (ClientException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '404') {
                $this->info("Router already removed, skipping");
                return;
            }
            throw $e;
        }

        # Delete the router
        $this->info("Removing router");
        $router->availabilityZone->nsxService()->delete('policy/api/v1/infra/tier-1s/' . $router->id);
    }
}
