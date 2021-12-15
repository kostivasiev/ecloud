<?php

namespace App\Jobs\Network;

use App\Jobs\TaskJob;
use GuzzleHttp\Exception\ClientException;

class Undeploy extends TaskJob
{
    public function handle()
    {
        $network = $this->task->resource;

        try {
            $network->router->availabilityZone->nsxService()->get(
                'policy/api/v1/infra/tier-1s/' . $network->router->id . '/segments/' . $network->id
            );
        } catch (ClientException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '404') {
                $this->info("Router already removed, skipping");
                return;
            }

            throw $e;
        }

        $this->info('Removing network ' . $network->id);
        $network->router->availabilityZone->nsxService()->delete(
            'policy/api/v1/infra/tier-1s/' . $network->router->id . '/segments/' . $network->id
        );
    }
}
