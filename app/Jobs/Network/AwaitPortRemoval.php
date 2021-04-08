<?php

namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\Sync;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitPortRemoval extends Job
{
    use Batchable;

    public $tries = 120;
    public $backoff = 5;

    private $network;

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
            'policy/api/v1/infra/tier-1s/' . $this->network->router->id . '/segments/' . $this->network->id . '/ports?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());
        if ($response->result_count > 0) {
            Log::info(
                'Waiting for all ports to be removed, retrying in ' . $this->backoff . ' seconds'
            );
            return $this->release($this->backoff);
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->network->id]);
    }
}
