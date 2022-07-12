<?php

namespace App\Traits\V2\Jobs\Instance;

use App\Models\V2\ResourceTier;

trait ResolveHostGroup
{
    public function resolveHostGroup(): ?string
    {
        $instance = $this->task->resource;
        $hostGroupId = $this->task->data['host_group_id'] ?? null;
        $resourceTierId = $this->task->data['resource_tier_id'] ?? null;
        $defaultResourceTierId = $instance->availabilityZone->resource_tier_id;

        if (!$hostGroupId) {
            $resourceTier = ResourceTier::find($resourceTierId ?? $defaultResourceTierId);
            $hostGroupId = $resourceTier->getDefaultHostGroup()->id;
        }

        if ($instance->host_group_id == $hostGroupId) {
            $this->info('Instance ' . $instance->id . ' is already in the host group ' . $hostGroupId . ', nothing to do');
            return null;
        }

        return $hostGroupId;
    }
}
