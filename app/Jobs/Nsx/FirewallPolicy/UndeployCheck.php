<?php

namespace App\Jobs\Nsx\FirewallPolicy;

use App\Jobs\TaskJob;

class UndeployCheck extends TaskJob
{
    public $tries = 60;
    public $backoff = 5;

    public function handle()
    {
        $firewallPolicy = $this->task->resource;

        $response = $firewallPolicy->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/domains/default/gateway-policies/?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            if ($firewallPolicy->id === $result->id) {
                $this->info(
                    'Waiting for ' . $firewallPolicy->id . ' being deleted, retrying in ' . $this->backoff . ' seconds'
                );
                $this->release($this->backoff);
                return;
            }
        }
    }
}
