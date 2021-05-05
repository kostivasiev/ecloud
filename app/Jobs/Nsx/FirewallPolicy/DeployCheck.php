<?php

namespace App\Jobs\Nsx\FirewallPolicy;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeployCheck extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 60;
    public $backoff = 5;

    private $model;

    public function __construct(FirewallPolicy $firewallPolicy)
    {
        $this->model = $firewallPolicy;
    }

    public function handle()
    {
        // NSX doesn't try to "realise" a FirewallPolicy until it has rules
        if (!count($this->model->firewallRules)) {
            Log::info('No rules on the policy. Ignoring deploy check');
            return;
        }

        $response = $this->model->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/gateway-policies/' . $this->model->id
        );
        $response = json_decode($response->getBody()->getContents());
        if ($response->publish_status !== 'REALIZED') {
            Log::info(
                'Waiting for ' . $this->model->id . ' being deployed, retrying in ' . $this->backoff . ' seconds'
            );
            $this->release($this->backoff);
            return;
        }
    }
}
