<?php

namespace App\Jobs\Network;

use App\Jobs\TaskJob;

class DeploySecurityProfile extends TaskJob
{
    public function handle()
    {
        $network = $this->task->resource;

        $response = $network->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/' . $network->router->id . '/segments/' . $network->id . '/segment-security-profile-binding-maps',
        );
        $response = json_decode($response->getBody()->getContents(), true);
        if (!isset($response['results'][0])) {
            $response['results'][0] = [
                'id' => $network->id . '-segment-security-profile-binding-maps',
            ];
        }
        $response['results'][0]['segment_security_profile_path'] = '/infra/segment-security-profiles/' . config('network.profiles.segment-security-profile');
        $response['results'][0]['spoofguard_profile_path'] = '/infra/spoofguard-profiles/' . config('network.profiles.spoofguard-profile');

        //tag resource with vpc-id
        $response['results'][0]['tags'] = [
            [
                'scope' => config('defaults.tag.scope'),
                'tag' => $network->router->vpc->id
            ]
        ];

        $network->router->availabilityZone->nsxService()->patch(
            'policy/api/v1/infra/tier-1s/' . $network->router->id . '/segments/' . $network->id . '/segment-security-profile-binding-maps/' . $response['results'][0]['id'],
            ['json' => $response['results'][0]]
        );
    }
}
