<?php

namespace App\Jobs\Nsx\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    private $model;

    public function __construct(Network $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $router = $this->model->router;

        // Delete Security profile
        Log::info('Deleting security profile');
        $response = $router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/' . $router->id . '/segments/' . $this->model->id . '/segment-security-profile-binding-maps',
        );
        $response = json_decode($response->getBody()->getContents(), true);
        if (isset($response['results'][0])) {
            $router->availabilityZone->nsxService()->delete(
                'policy/api/v1/infra/tier-1s/' . $router->id . '/segments/' . $this->model->id . '/segment-security-profile-binding-maps/' . $response['results'][0]['id']
            );
            Log::info('Deleted security profile ' . $response['results'][0]['id']);
        }

        // Delete Discovery profile
        Log::info('Deleting discovery profile');
        $response = $router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/' . $router->id . '/segments/' . $this->model->id . '/segment-discovery-profile-binding-maps',
        );
        $response = json_decode($response->getBody()->getContents(), true);
        if (isset($response['results'][0])) {
            $router->availabilityZone->nsxService()->delete(
                'policy/api/v1/infra/tier-1s/' . $router->id . '/segments/' . $this->model->id . '/segment-discovery-profile-binding-maps/' . $response['results'][0]['id'],
            );
            Log::info('Deleted discovery profile ' . $response['results'][0]['id']);
        }

        // Delete QOS profile
        Log::info('Deleting QOS profile');
        $response = $router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/' . $router->id . '/segments/' . $this->model->id . '/segment-qos-profile-binding-maps',
        );
        $response = json_decode($response->getBody()->getContents(), true);
        if (isset($response['results'][0])) {
            $router->availabilityZone->nsxService()->delete(
                'policy/api/v1/infra/tier-1s/' . $router->id . '/segments/' . $this->model->id . '/segment-qos-profile-binding-maps/' . $response['results'][0]['id'],
            );
            Log::info('Deleted QOS profile ' . $response['results'][0]['id']);
        }

        // Delete Network
        $this->model->router->availabilityZone->nsxService()->delete(
            'policy/api/v1/infra/tier-1s/' . $this->model->router->id . '/segments/' . $this->model->id
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function failed($exception)
    {
        $this->model->setSyncFailureReason('Failed to delete "' . $this->model->id . '"');
    }
}
