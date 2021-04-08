<?php

namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
{
    use Batchable;

    // Wait up to 30 minutes
    public $tries = 360;
    public $backoff = 5;

    private $network;

    public function __construct(Network $network)
    {
        $this->network = $network;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->network->id]);

        $response = $this->network->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/' . $this->network->router->id . '/segments/?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            if ($this->network->id === $result->id) {
                Log::info(
                    'Waiting for ' . $this->network->id . ' being deleted, retrying in ' . $this->backoff . ' seconds'
                );
                $this->release($this->backoff);
                return;
            }
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->network->id]);
    }
}
