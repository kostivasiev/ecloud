<?php

namespace App\Jobs\AffinityRuleMember;

use App\Jobs\TaskJob;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\AvailabilityZone;
use App\Services\V2\KingpinService;

class DeleteExistingRule extends TaskJob
{
    private AffinityRuleMember $affinityRuleMember;
    private AvailabilityZone $availabilityZone;

    public function handle()
    {
        $this->affinityRuleMember = $this->task->resource;
        $this->availabilityZone = $this->affinityRuleMember->instance->availabilityZone;

        $hostGroupId = $this->affinityRuleMember->instance->getHostGroupId();
        if (!$hostGroupId) {
            $message = 'HostGroup could not be retrieved for instance ' . $this->affinityRuleMember->instance->id;
            $this->fail($message);
            return;
        }

        if ($this->affinityRuleExists($hostGroupId)) {
            try {
                $this->info('Deleting affinity rule constraint '  . $this->affinityRuleMember->affinityRule->id . ' from host group ' . $hostGroupId);
                $response = $this->availabilityZone->kingpinService()->delete(
                    sprintf(KingpinService::DELETE_CONSTRAINT_URI, $hostGroupId, $this->affinityRuleMember->affinityRule->id)
                );
                $this->info('Rule ' . $this->affinityRuleMember->affinityRule->id . ' was removed from host group ' . $hostGroupId);

                if ($response->getStatusCode() !== 200) {
                    $message = 'Failed to delete constraint ' . $this->affinityRuleMember->id . ' on ' . $hostGroupId;
                    $this->fail(new \Exception($message));
                    return;
                }
            } catch (\Exception $e) {
                $this->fail($e);
                return;
            }
        }
    }

    public function affinityRuleExists(string $hostGroupId): bool
    {
        try {
            $response = $this->availabilityZone->kingpinService()
                ->get(
                    sprintf(KingpinService::GET_CONSTRAINT_URI, $hostGroupId)
                );
        } catch (\Exception $e) {
            $message = 'Failed to retrieve ' . $hostGroupId . ' : ' . $e->getMessage();
            $this->info($message);
            return false;
        }
        return collect(json_decode($response->getBody()->getContents(), true))
                ->where('ruleName', '=', $this->affinityRuleMember->affinityRule->id)
                ->count() > 0;
    }
}
