<?php

namespace App\Jobs\Router;

use App\Jobs\TaskJob;
use GuzzleHttp\Exception\ClientException;

class UndeployRouterLocale extends TaskJob
{
    /**
     * @throws \Exception
     */
    public function handle()
    {
        $router = $this->task->resource;

        try {
            $router->availabilityZone->nsxService()->get('policy/api/v1/infra/tier-1s/' . $router->id . '/locale-services/' . $router->id);
        } catch (ClientException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '404') {
                $this->info("Router locale already removed, skipping");
                return;
            }

            throw $e;
        }

        $this->info("Removing router locale");
        $router->availabilityZone->nsxService()
            ->delete('policy/api/v1/infra/tier-1s/' . $router->id . '/locale-services/' . $router->id);
    }
}
