<?php

namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UndeployDiscoveryProfiles extends Job
{
    use Batchable, LoggableModelJob;

    private Network $model;

    public function __construct(Network $network)
    {
        $this->model = $network;
    }

    public function handle()
    {
        try {
            $this->model->router->availabilityZone->nsxService()->get(
                'policy/api/v1/infra/tier-1s/' . $this->model->router->id . '/segments/' . $this->model->id
            );
        } catch (ClientException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '404') {
                Log::info("Network already removed, skipping");
                return;
            }

            throw $e;
        }

        $response = $this->model->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/' . $this->model->router->id . '/segments/' . $this->model->id . '/segment-discovery-profile-binding-maps',
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            $this->model->router->availabilityZone->nsxService()->delete(
                'policy/api/v1/infra/tier-1s/' . $this->model->router->id . '/segments/' . $this->model->id . '/segment-discovery-profile-binding-maps/' . $result->id,
            );
            Log::info('Deleted discovery profile ' . $result->id);
        }
    }
}
