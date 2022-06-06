<?php

namespace App\Jobs\AffinityRuleMember;

use App\Jobs\TaskJob;
use App\Models\V2\AffinityRuleMember;
use App\Services\V2\KingpinService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CreateAffinityRule extends TaskJob
{
    public function handle()
    {
        if ($this->task->resource->affinityRule->affinityRuleMembers()->count() <= 0) {
            Log::info('Rule has no members, skipping', [
                'affinity_rule_id' => $this->task->resource->id,
            ]);
            return;
        }

        if ($this->task->resource->affinityRule->affinityRuleMembers()->count() < 2) {
            Log::info('Affinity rules need at least two members', [
                'affinity_rule_id' => $this->task->resource->id,
                'member_count' => $this->task->resource->affinityRule->affinityRuleMembers()->count(),
            ]);
            return;
        }

        $hostGroupId = $this->task->resource->instance->getHostGroupId();
        $affinityRuleMembers = $this->task->resource->affinityRule->affinityRuleMembers()->get();
        $instanceIds = $affinityRuleMembers->filter(
            function (AffinityRuleMember $affinityRuleMember) use ($hostGroupId) {
                if ($affinityRuleMember->instance->id !== $this->task->resource->instance->id) {
                    if ($affinityRuleMember->instance->getHostGroupId() == $hostGroupId) {
                        return $affinityRuleMember->instance->id;
                    }
                }
            }
        )->pluck('instance_id');

        try {
            $this->createAffinityRule($hostGroupId, $instanceIds);
        } catch (\Exception $e) {
            $this->fail($e);
            return;
        }
    }

    public function createAffinityRule(string $hostGroupId, Collection $instanceIds)
    {
        $availabilityZone = $this->task->resource->instance->availabilityZone;
        $uriEndpoint = ($this->task->resource->type == 'affinity') ?
            KingpinService::AFFINITY_URI :
            KingpinService::ANTI_AFFINITY_URI;

        try {
            $response = $availabilityZone->kingpinService()->post(
                sprintf($uriEndpoint, $hostGroupId),
                [
                    'json' => [
                        'ruleName' => $this->task->resource->affinityRule->id,
                        'vpcId' => $this->task->resource->affinityRule->vpc->id,
                        'instanceIds' => $instanceIds,
                    ],
                ]
            );
        } catch (\Exception $e) {
            Log::info('Failed to create affinity rule', [
                'affinity_rule_id' => $this->task->resource->affinityRule->id,
                'hostgroup_id' => $hostGroupId,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
        if ($response->getStatusCode() == 200) {
            $createdRules = [];
            if (isset($this->task->data['created_rules'])) {
                $createdRules = $this->task->data['created_rules'];
            }
            $createdRules[] = $hostGroupId;
            $this->task->updateData('created_rules', $createdRules);
            return true;
        } else {
            throw new \Exception('Failed to create rule');
        }
        return false;
    }
}
