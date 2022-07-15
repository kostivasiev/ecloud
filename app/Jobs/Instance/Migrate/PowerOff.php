<?php

namespace App\Jobs\Instance\Migrate;

use App\Jobs\TaskJob;
use App\Models\V2\HostGroup;

class PowerOff extends TaskJob
{
    public function handle()
    {
        $instance = $this->task->resource;

        $hostGroup = HostGroup::find($this->task->data['host_group_id']);
        if (!$hostGroup) {
            $this->fail(new \Exception('Failed to load host group ' . $this->task->data['host_group_id']));
            return;
        }

        if ($hostGroup->hostSpec->id != $instance->hostGroup->hostSpec->id) {
            $this->info('New host spec differs from existing host spec, powering off instance for migration.', [
                'instance_id' => $instance->id,
                'current_host_spec' => $instance->hostGroup->hostSpec->id,
                'new_host_spec' => $hostGroup->hostSpec->id,
            ]);
            dispatch_sync(new \App\Jobs\Instance\PowerOff($instance));
        }
    }
}
