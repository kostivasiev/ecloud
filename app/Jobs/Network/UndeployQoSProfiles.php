<?php

namespace App\Jobs\Network;

use App\Jobs\TaskJob;
use GuzzleHttp\Exception\ClientException;

class UndeployQoSProfiles extends TaskJob
{
    // TODO: We don't create QoS profiles for segments, however this is here for earlier networks which were created with them
    public function handle()
    {
        $network = $this->task->resource;

        try {
            $network->router->availabilityZone->nsxService()->get(
                'policy/api/v1/infra/tier-1s/' . $network->router->id . '/segments/' . $network->id
            );
        } catch (ClientException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '404') {
                $this->info("Network already removed, skipping");
                return;
            }

            throw $e;
        }

        $response = $network->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/' . $network->router->id . '/segments/' . $network->id . '/segment-qos-profile-binding-maps',
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            $network->router->availabilityZone->nsxService()->delete(
                'policy/api/v1/infra/tier-1s/' . $network->router->id . '/segments/' . $network->id . '/segment-qos-profile-binding-maps/' . $result->id,
            );
            $this->info('Deleted qos profile ' . $result->id);
        }
    }
}
