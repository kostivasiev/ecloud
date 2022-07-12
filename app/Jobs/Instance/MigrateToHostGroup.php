<?php

namespace App\Jobs\Instance;

use App\Jobs\TaskJob;
use App\Models\V2\ResourceTier;
use App\Traits\V2\Jobs\Instance\ResolveHostGroup;

class MigrateToHostGroup extends TaskJob
{
    use ResolveHostGroup;

    public function handle()
    {
        $instance = $this->task->resource;
        $hostGroupId = $this->resolveHostGroup();

        if ($hostGroupId) {
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
}
