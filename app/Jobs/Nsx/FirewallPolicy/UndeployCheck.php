<?php

namespace App\Jobs\Nsx\FirewallPolicy;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
{
    use Batchable;

    private $firewallPolicy;

    public $tries = 60;
    public $backoff = 5;


    public function __construct(FirewallPolicy $firewallPolicy)
    {
        $this->firewallPolicy = $firewallPolicy;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->firewallPolicy->id]);

        $response = $this->firewallPolicy->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/domains/default/gateway-policies/?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            if ($this->firewallPolicy->id === $result->id) {
                Log::info(
                    'Waiting for ' . $this->firewallPolicy->id . ' being deleted, retrying in ' . $this->backoff . ' seconds'
                );
                $this->release($this->backoff);
                return;
            }
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->firewallPolicy->id]);
    }
}
