<?php

namespace App\Jobs\Instance;

use App\Jobs\TaskJob;

class MigrateToHostGroup extends TaskJob
{
    public function handle()
    {
        $instance = $this->task->resource;
        $hostGroupId = $this->task->data['host_group_id'];

        if ($instance->host_group_id == $hostGroupId) {
            $this->info('Instance ' . $instance->id . ' is already in the host group ' . $hostGroupId . ', nothing to do');
            return;
        }

        $instance->availabilityZone->kingpinService()
            ->post(
                '/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->id . '/reschedule',
                [
                    'json' => [
                        'hostGroupId' => $hostGroupId,
                    ],
                ]
            );

        $this->info('Instance ' . $instance->id . ' was moved to host group ' . $hostGroupId);
    }
}
