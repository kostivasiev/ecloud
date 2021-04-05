<?php

namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UndeploySecurityProfile extends Job
{
    use Batchable;

    private Network $network;

    public function __construct(Network $network)
    {
        $this->network = $network;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->network->id]);

        $response = $this->network->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/' . $this->network->router->id . '/segments/' . $this->network->id . '/segment-security-profile-binding-maps',
        );
        $response = json_decode($response->getBody()->getContents(), true);
        if (isset($response['results'][0])) {
            $this->network->router->availabilityZone->nsxService()->delete(
                'policy/api/v1/infra/tier-1s/' . $this->network->router->id . '/segments/' . $this->network->id . '/segment-security-profile-binding-maps/' . $response['results'][0]['id']
            );
            Log::info('Deleted security profile ' . $response['results'][0]['id']);
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->network->id]);
    }
}
