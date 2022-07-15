<?php

namespace App\Jobs\Instance\Migrate;

use App\Jobs\TaskJob;
use App\Models\V2\HostGroup;
use App\Services\V2\KingpinService;

class PowerOn extends TaskJob
{
    public function handle()
    {
        $instance = $this->task->resource;

        if (empty($this->task->data['requires_power_cycle'])) {
            return;
        }

        $hostGroup = HostGroup::find($this->task->data['host_group_id']);
        if (!$hostGroup) {
            $this->fail(new \Exception('Failed to load host group ' . $this->task->data['host_group_id']));
            return;
        }

        $getInstanceResponse = $instance->availabilityZone->kingpinService()->get(
            sprintf(KingpinService::GET_INSTANCE_URI, $instance->vpc->id, $instance->id)
        );

        $instanceData = json_decode($getInstanceResponse->getBody()->getContents());

        if ($instanceData->powerState == KingpinService::INSTANCE_POWERSTATE_POWEREDOFF) {
            $this->info('Powering on instance post-migration', [
                'instance_id' => $instance->id
            ]);
            dispatch_sync(new \App\Jobs\Instance\PowerOn($instance));
        }
    }
}
