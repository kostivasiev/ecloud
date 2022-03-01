<?php

namespace App\Jobs\Nsx\Dhcp;

use App\Jobs\TaskJob;

class UndeployCheck extends TaskJob
{
    public $tries = 60;
    public $backoff = 5;

    public function handle()
    {
        $dhcp = $this->task->resource;

        $response = $dhcp->availabilityZone->nsxService()->get(
            '/policy/api/v1/infra/dhcp-server-configs/?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            if ($dhcp->id === $result->id) {
                $this->info(
                    'Waiting for ' . $dhcp->id . ' being deleted, retrying in ' . $this->backoff . ' seconds'
                );
                $this->release($this->backoff);
                return;
            }
        }
    }
}
