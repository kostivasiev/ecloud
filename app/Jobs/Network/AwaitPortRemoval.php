<?php

namespace App\Jobs\Network;

use App\Jobs\TaskJob;
use GuzzleHttp\Exception\ClientException;

class AwaitPortRemoval extends TaskJob
{
    public $tries = 120;
    public $backoff = 5;

    public function handle()
    {
        $network = $this->task->resource;

        $segmentUniqueID = null;
        try {
            $response = $network->router->availabilityZone->nsxService()->get(
                'policy/api/v1/infra/tier-1s/' . $network->router->id . '/segments/' . $network->id
            );
            $response = json_decode($response->getBody()->getContents());
            $segmentUniqueID = $response->unique_id;
        } catch (ClientException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '404') {
                $this->info("Network already removed, skipping");
                return;
            }

            throw $e;
        }

        $response = $network->router->availabilityZone->nsxService()->get(
            '/api/v1/logical-ports?logical_switch_id=' . $segmentUniqueID
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            if ($result->attachment->attachment_type == 'VIF') {
                $this->info(
                    'Waiting for all ports to be removed, retrying in ' . $this->backoff . ' seconds'
                );
                $this->release($this->backoff);
                return;
            }
        }
    }
}
