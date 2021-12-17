<?php

namespace App\Jobs\Nsx\FirewallPolicy;

use App\Jobs\TaskJob;
use App\Services\V2\NsxService;

class DeployCheck extends TaskJob
{
    public $tries = 60;
    public $backoff = 5;

    public function handle()
    {
        $firewallPolicy = $this->task->resource;

        // NSX doesn't try to "realise" a FirewallPolicy until it has rules
        if (!count($firewallPolicy->firewallRules)) {
            $this->info('No rules on the policy. Ignoring deploy check');
            return;
        }

        $response = $firewallPolicy->router->availabilityZone->nsxService()->get(
            sprintf(NsxService::GET_REALISED_STATE_GATEWAY_POLICY, $firewallPolicy->id)
        );
        $response = json_decode($response->getBody()->getContents());
        if ($response->publish_status !== 'REALIZED') {
            $this->info(
                'Waiting for ' . $firewallPolicy->id . ' being deployed, retrying in ' . $this->backoff . ' seconds'
            );
            $this->release($this->backoff);
        }
    }
}
