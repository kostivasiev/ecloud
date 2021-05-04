<?php

namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeployDiscoveryProfile extends Job
{
    use Batchable, JobModel;

    private Network $network;

    public function __construct(Network $network)
    {
        $this->network = $network;
    }

    public function handle()
    {
        $response = $this->network->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/' . $this->network->router->id . '/segments/' . $this->network->id . '/segment-discovery-profile-binding-maps',
        );
        $response = json_decode($response->getBody()->getContents(), true);
        if (!isset($response['results'][0])) {
            $response['results'][0] = [
                'id' => $this->network->id . '-segment-discovery-profile-binding-maps',
            ];
        }
        $response['results'][0]['ip_discovery_profile_path'] = '/infra/ip-discovery-profiles/' . config('network.profiles.ip-discovery-profile');
        $response['results'][0]['mac_discovery_profile_path'] = '/infra/mac-discovery-profiles/' . config('network.profiles.mac-discovery-profile');
        $this->network->router->availabilityZone->nsxService()->patch(
            'policy/api/v1/infra/tier-1s/' . $this->network->router->id . '/segments/' . $this->network->id . '/segment-discovery-profile-binding-maps/' . $response['results'][0]['id'],
            ['json' => $response['results'][0]]
        );
    }
}
