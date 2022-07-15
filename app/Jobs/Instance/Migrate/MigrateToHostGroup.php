<?php

namespace App\Jobs\Instance\Migrate;

use App\Events\V2\Instance\Migrated;
use App\Jobs\TaskJob;
use App\Models\V2\HostGroup;

class MigrateToHostGroup extends TaskJob
{
    public function handle()
    {
        $instance = $this->task->resource;

        $hostGroup = HostGroup::find($this->task->data['host_group_id']);
        if (!$hostGroup) {
            $this->fail(new \Exception('Failed to load host group ' . $this->task->data['host_group_id']));
            return;
        }

        if ($hostGroup->id === $instance->host_group_id) {
            $this->info('Instance is already in the requested host group, skipping');
            return;
        }

        $instance->availabilityZone->kingpinService()
            ->post(
                '/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->id . '/reschedule',
                [
                    'json' => [
                        'hostGroupId' => HostGroup::mapId($hostGroup->id),
                    ],
                ]
            );

        $this->info('Instance ' . $instance->id . ' was migrated to host group ' . $hostGroup->id);

        event(new Migrated($instance, $hostGroup));
    }
}
