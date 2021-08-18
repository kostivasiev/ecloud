<?php

namespace App\Jobs\Sync\VpnService;

use App\Jobs\Job;
use App\Jobs\Nsx\DeployCheck;
use App\Jobs\Nsx\VpnService\Deploy;
use App\Jobs\Nsx\VpnService\RetrieveServiceUuid;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;

class Update extends Job
{
    use TaskableBatch, LoggableTaskJob;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $this->updateTaskBatch([
            [
                new Deploy($this->task->resource),
                // TODO: Why are we doing this? what is this for? Isn't the resource ID the model ID we pass in?
//                new RetrieveServiceUuid($this->task->resource),
                new DeployCheck(
                    $this->task->resource,
                    $this->task->resource->router->availabilityZone,
                    '/infra/tier-1s/' . $this->task->resource->router->id .
                    '/locale-services/' . $this->task->resource->router->id .
                    '/ipsec-vpn-services/'
                ),
            ],
        ])->dispatch();
    }
}
