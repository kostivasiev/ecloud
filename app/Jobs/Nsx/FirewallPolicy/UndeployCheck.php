<?php

namespace App\Jobs\Nsx\FirewallPolicy;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
{
    use Batchable, JobModel;

    private $model;

    public $tries = 60;
    public $backoff = 5;


    public function __construct(FirewallPolicy $firewallPolicy)
    {
        $this->model = $firewallPolicy;
    }

    public function handle()
    {
        $response = $this->model->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/domains/default/gateway-policies/?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            if ($this->model->id === $result->id) {
                Log::info(
                    'Waiting for ' . $this->model->id . ' being deleted, retrying in ' . $this->backoff . ' seconds'
                );
                $this->release($this->backoff);
                return;
            }
        }
    }
}
