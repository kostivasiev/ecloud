<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\HostGroup;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use GuzzleHttp\Psr7\Response;
use Illuminate\Bus\Batchable;

class Deploy extends Job
{
    use Batchable, LoggableTaskJob;

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $instance = $this->task->resource;

        if (empty($instance->image)) {
            $this->fail(new \Exception(
                'Deploy failed for ' . $instance->id . ', Failed to load image'
            ));
            return;
        }

        $deployData = [
            'templateName' => $instance->image->vm_template,
            'instanceId' => $instance->getKey(),
            'numCPU' => $instance->vcpu_cores,
            'ramMib' => $instance->ram_capacity,
            'backupEnabled' => $instance->backup_enabled,
            'hostGroupId' => HostGroup::mapId($instance->host_group_id),
        ];

        /** @var Response $deployResponse */
        $deployResponse = $instance->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $instance->vpc->id . '/instance/fromtemplate',
            [
                'json' => $deployData
            ]
        );

        $deployResponse = json_decode($deployResponse->getBody()->getContents());
        if (!$deployResponse) {
            throw new \Exception('Deploy failed for ' . $instance->id . ', could not decode response');
        }
    }
}
