<?php

namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class DeploySecurityProfile extends Job
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
            'policy/api/v1/infra/tier-1s/' . $this->model->router->id . '/segments/' . $this->model->id . '/segment-security-profile-binding-maps',
        );
        $response = json_decode($response->getBody()->getContents(), true);
        if (!isset($response['results'][0])) {
            $response['results'][0] = [
                'id' => $this->model->id . '-segment-security-profile-binding-maps',
            ];
        }
        $response['results'][0]['segment_security_profile_path'] = '/infra/segment-security-profiles/' . config('network.profiles.segment-security-profile');
        $response['results'][0]['spoofguard_profile_path'] = '/infra/spoofguard-profiles/' . config('network.profiles.spoofguard-profile');
        $this->model->router->availabilityZone->nsxService()->patch(
            'policy/api/v1/infra/tier-1s/' . $this->model->router->id . '/segments/' . $this->model->id . '/segment-security-profile-binding-maps/' . $response['results'][0]['id'],
            ['json' => $response['results'][0]]
        );
    }
}
