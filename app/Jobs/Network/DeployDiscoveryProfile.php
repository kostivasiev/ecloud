<?php

namespace App\Jobs\Network;

use App\Jobs\TaskJob;

class DeployDiscoveryProfile extends TaskJob
{
    public function handle()
    {
        $network = $this->task->resource;

        $response = $network->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/' . $network->router->id . '/segments/' . $network->id . '/segment-discovery-profile-binding-maps',
        );
        $response = json_decode($response->getBody()->getContents(), true);
        if (!isset($response['results'][0])) {
            $response['results'][0] = [
                'id' => $network->id . '-segment-discovery-profile-binding-maps',
            ];
        }
        $response['results'][0]['ip_discovery_profile_path'] = '/infra/ip-discovery-profiles/' . config('network.profiles.ip-discovery-profile');
        $response['results'][0]['mac_discovery_profile_path'] = '/infra/mac-discovery-profiles/' . config('network.profiles.mac-discovery-profile');

        //tag resource with vpc-id
        $response['results'][0]['tags'] = [
            [
                'scope' => config('defaults.tag.scope'),
                'tag' => $network->router->vpc->id
            ]
        ];

        $network->router->availabilityZone->nsxService()->patch(
            'policy/api/v1/infra/tier-1s/' . $network->router->id . '/segments/' . $network->id . '/segment-discovery-profile-binding-maps/' . $response['results'][0]['id'],
            ['json' => $response['results'][0]]
        );
    }
}
