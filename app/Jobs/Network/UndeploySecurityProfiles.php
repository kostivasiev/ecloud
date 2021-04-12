<?php

namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UndeploySecurityProfiles extends Job
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

        try {
            $this->network->router->availabilityZone->nsxService()->get(
                'policy/api/v1/infra/tier-1s/' . $this->network->router->id . '/segments/' . $this->network->id
            );
        } catch (ClientException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '404') {
                Log::info("Network already removed, skipping");
                return;
            }

            throw $e;
        }

        $response = $this->network->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/' . $this->network->router->id . '/segments/' . $this->network->id . '/segment-security-profile-binding-maps',
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            $this->network->router->availabilityZone->nsxService()->delete(
                'policy/api/v1/infra/tier-1s/' . $this->network->router->id . '/segments/' . $this->network->id . '/segment-security-profile-binding-maps/' . $result->id
            );
            Log::info('Deleted security profile ' . $result->id);
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->network->id]);
    }
}
