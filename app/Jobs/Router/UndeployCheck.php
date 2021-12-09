<?php

namespace App\Jobs\Router;

use App\Jobs\TaskJob;

class UndeployCheck extends TaskJob
{
    // Wait up to 30 minutes
    public $tries = 360;
    public $backoff = 5;

    public function handle()
    {
        $router = $this->task->resource;

        $response = $router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            if ($router->id === $result->id) {
                $this->info(
                    'Waiting for ' . $router->id . ' being deleted, retrying in ' . $this->backoff . ' seconds'
                );
                $this->release($this->backoff);
                return;
            }
        }
    }
}
