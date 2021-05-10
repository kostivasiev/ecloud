<?php

namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class DeployDiscoveryProfile extends Job
{
    use Batchable, LoggableModelJob;

    private Network $model;

    public function __construct(Network $network)
    {
        $this->model = $network;
    }

    public function handle()
    {
        $response = $this->model->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/' . $this->model->router->id . '/segments/' . $this->model->id . '/segment-discovery-profile-binding-maps',
        );
        $response = json_decode($response->getBody()->getContents(), true);
        if (!isset($response['results'][0])) {
            $response['results'][0] = [
                'id' => $this->model->id . '-segment-discovery-profile-binding-maps',
            ];
        }
        $response['results'][0]['ip_discovery_profile_path'] = '/infra/ip-discovery-profiles/' . config('network.profiles.ip-discovery-profile');
        $response['results'][0]['mac_discovery_profile_path'] = '/infra/mac-discovery-profiles/' . config('network.profiles.mac-discovery-profile');
        $this->model->router->availabilityZone->nsxService()->patch(
            'policy/api/v1/infra/tier-1s/' . $this->model->router->id . '/segments/' . $this->model->id . '/segment-discovery-profile-binding-maps/' . $response['results'][0]['id'],
            ['json' => $response['results'][0]]
        );
    }
}
