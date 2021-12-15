<?php

namespace App\Jobs\Network;

use App\Jobs\TaskJob;

class UndeployCheck extends TaskJob
{
    // Wait up to 30 minutes
    public $tries = 360;
    public $backoff = 5;

    public function handle()
    {
        $network = $this->task->resource;

        $response = $network->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/' . $network->router->id . '/segments/?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            if ($network->id === $result->id) {
                $this->info(
                    'Waiting for ' . $network->id . ' being deleted, retrying in ' . $this->backoff . ' seconds'
                );
                $this->release($this->backoff);
                return;
            }
        }
    }
}
