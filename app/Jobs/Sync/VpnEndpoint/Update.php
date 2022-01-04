<?php

namespace App\Jobs\Sync\VpnEndpoint;

use App\Jobs\Job;
use App\Jobs\Nsx\DeployCheck;
use App\Jobs\Nsx\VpnEndpoint\Deploy;
use App\Jobs\VpnEndpoint\CreateFloatingIp;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;

class Update extends Job
{
    use TaskableBatch, LoggableTaskJob;

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $this->updateTaskBatch([
            [
                new CreateFloatingIp($this->task->resource, $this->task),
                new Deploy($this->task->resource),
                new DeployCheck(
                    $this->task->resource,
                    $this->task->resource->vpnService->router->availabilityZone,
                    '/infra/tier-1s/' . $this->task->resource->vpnService->router->id .
                    '/locale-services/' . $this->task->resource->vpnService->router->id .
                    '/ipsec-vpn-services/' . $this->task->resource->vpnService->id . '/local-endpoints/'
                ),
            ],
        ])->dispatch();
    }
}
