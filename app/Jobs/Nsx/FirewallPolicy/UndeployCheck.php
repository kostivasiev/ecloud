<?php

namespace App\Jobs\Nsx\FirewallPolicy;

use App\Jobs\TaskJob;
use App\Services\V2\NsxService;

class UndeployCheck extends TaskJob
{
    public $tries = 60;
    public $backoff = 5;

    public function handle()
    {
        $firewallPolicy = $this->task->resource;

        $response = $firewallPolicy->router->availabilityZone->nsxService()->get(
            sprintf(NsxService::GET_GATEWAY_POLICIES, '?include_mark_for_delete_objects=true')
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
