<?php

namespace App\Jobs\Instance;

use App\Events\V2\Instance\Migrated;
use App\Jobs\TaskJob;
use App\Models\V2\HostGroup;
use App\Models\V2\ResourceTier;

class MigrateToHostGroup extends TaskJob
{
    public function handle()
    {
        $instance = $this->task->resource;

        if (!empty($this->task->data['host_group_id'])) {
            $hostGroup = HostGroup::find($this->task->data['host_group_id']);
        } else {
            $resourceTier = ResourceTier::find(
                $this->task->data['resource_tier_id'] ??
                $instance->availabilityZone->resource_tier_id
            );

            $hostGroup = $resourceTier->getDefaultHostGroup();
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

        $this->info('Instance ' . $instance->id . ' was moved to host group ' . $hostGroup->id);

        event(new Migrated($instance, $hostGroup));
    }
}
