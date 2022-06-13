<?php

namespace App\Jobs\AffinityRuleMember;

use App\Jobs\TaskJob;
use App\Services\V2\KingpinService;

class DeleteExistingRule extends TaskJob
{
    public function handle()
    {
        $affinityRuleMember = $this->task->resource;
        $availabilityZone = $affinityRuleMember->instance->availabilityZone;

        $instance = $affinityRuleMember->instance;
        $hostGroupId = $instance->getHostGroupId();
        if (!$hostGroupId) {
            $message = 'HostGroup could not be retrieved for instance ' . $instance->id;
            $this->fail(new \Exception($message));
            return;
        }

        try {
            if ($instance->hasAffinityRule($hostGroupId, $affinityRuleMember->affinityRule->id)) {
                try {
                    $this->info('Deleting affinity rule constraint ' . $affinityRuleMember->affinityRule->id . ' from host group ' . $hostGroupId);
                    $response = $availabilityZone->kingpinService()->delete(
                        sprintf(
                            KingpinService::DELETE_CONSTRAINT_URI,
                            $hostGroupId,
                            $affinityRuleMember->affinityRule->id
                        )
                    );
                    $this->info('Rule ' . $affinityRuleMember->affinityRule->id . ' was removed from host group ' . $hostGroupId);

                    if ($response->getStatusCode() !== 200) {
                        $message = 'Failed to delete constraint ' . $affinityRuleMember->id . ' on ' . $hostGroupId;
                        $this->fail(new \Exception($message));
                        return;
                    }
                } catch (\Exception $e) {
                    $this->fail($e);
                    return;
                }
            }
        } catch (\Exception $e) {
            $this->info($e->getMessage());
            return;
        }
    }
}
