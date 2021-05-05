<?php

namespace App\Jobs\Sync\NetworkPolicy;

use App\Jobs\Job;
use App\Jobs\NetworkRule\CreateDefaultNetworkRules;
use App\Jobs\Nsx\NetworkPolicy\SecurityGroup\Deploy as DeploySecurityGroup;
use App\Jobs\Nsx\NetworkPolicy\Deploy as DeployNetworkPolicy;
use App\Jobs\Nsx\DeployCheck;
use App\Models\V2\Task;
use App\Traits\V2\TaskableBatch;
use Illuminate\Support\Facades\Log;

class Update extends Job
{
    use TaskableBatch;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->task->id, 'resource_id' => $this->task->resource->id]);

        $this->updateTaskBatch([
            [
                new DeploySecurityGroup($this->task->resource),
                new DeployCheck(
                    $this->task->resource,
                    $this->task->resource->network->router->availabilityZone,
                    '/infra/domains/default/groups/'
                ),
                new CreateDefaultNetworkRules($this->task->resource),
                new DeployNetworkPolicy($this->task->resource),
                new DeployCheck(
                    $this->task->resource,
                    $this->task->resource->network->router->availabilityZone,
                    '/infra/domains/default/security-policies/'
                )
            ]
        ])->dispatch();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->task->id, 'resource_id' => $this->task->resource->id]);
    }
}
