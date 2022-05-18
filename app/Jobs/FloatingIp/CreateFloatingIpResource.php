<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\TaskJob;
use App\Models\V2\FloatingIpResource;
use App\Support\Resource;
use App\Traits\V2\TaskJobs\AwaitResources;

class CreateFloatingIpResource extends TaskJob
{
    use AwaitResources;

    public function handle()
    {
        $floatingIp = $this->task->resource;

        $resource = Resource::getFromId($this->task->data['resource_id']);

        if (!$resource) {
            $this->fail(new \Exception('Failed to load resource ' . $this->task->data['resource_id']));
            return;
        }

        $this->createSyncableResource(
            FloatingIpResource::class,
            [
                'floating_ip_id' => $floatingIp->id
            ],
            function ($floatingIpResource) use ($resource) {
                $floatingIpResource->resource()->associate($resource);
            });
    }
}