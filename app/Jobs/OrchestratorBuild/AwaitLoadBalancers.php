<?php

namespace App\Jobs\OrchestratorBuild;

use App\Jobs\Job;
use App\Models\V2\OrchestratorBuild;
use App\Support\Resource;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AwaitLoadBalancers extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;

    private OrchestratorBuild $model;

    private Collection $state;

    public function __construct(OrchestratorBuild $orchestratorBuild)
    {
        $this->model = $orchestratorBuild;
        $orchestratorBuild->refresh();

        $this->state = collect($orchestratorBuild->state);

        $nodeCount = 0;
        if ($this->state->has('load_balancer')) {
            collect($this->state->get('load_balancer'))->each(function ($loadBalancerId) use (&$nodeCount) {
                $loadBalancer = Resource::classFromId($loadBalancerId)::find($loadBalancerId);
                if ($loadBalancer) {
                    $nodeCount += $loadBalancer->loadBalancerSpec->node_count;
                }
            });

            // Set timeout to 20 mins per node
            $timeout = (240 * $nodeCount);
            $this->tries = ($timeout > $this->tries) ? $timeout : $this->tries;
        }
    }

    public function handle()
    {
        if (!$this->state->has('load_balancer')) {
            Log::info(get_class($this) . ' : No load balancer\'s detected in build state, skipping', ['id' => $this->model->id]);
            return;
        }

        $this->awaitSyncableResources($this->state->get('load_balancer'));
    }
}
