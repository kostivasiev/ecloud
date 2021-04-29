<?php

namespace App\Jobs\Nsx\FirewallPolicy;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeployCheck extends Job
{
    use Batchable;

    public $tries = 60;
    public $backoff = 5;

    private $firewallPolicy;

    public function __construct(FirewallPolicy $firewallPolicy)
    {
        $this->firewallPolicy = $firewallPolicy;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->firewallPolicy->id]);

        // NSX doesn't try to "realise" a FirewallPolicy until it has rules
        if (!count($this->firewallPolicy->firewallRules)) {
            Log::info('No rules on the policy. Ignoring deploy check');
            return;
        }

        $response = $this->firewallPolicy->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/gateway-policies/' . $this->firewallPolicy->id
        );
        $response = json_decode($response->getBody()->getContents());
        if ($response->publish_status !== 'REALIZED') {
            Log::info(
                'Waiting for ' . $this->firewallPolicy->id . ' being deployed, retrying in ' . $this->backoff . ' seconds'
            );
            $this->release($this->backoff);
            return;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->firewallPolicy->id]);
    }
}
