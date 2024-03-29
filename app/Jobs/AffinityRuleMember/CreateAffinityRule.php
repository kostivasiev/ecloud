<?php

namespace App\Jobs\AffinityRuleMember;

use App\Jobs\TaskJob;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\HostGroup;
use App\Services\V2\KingpinService;
use App\Support\Sync;
use Illuminate\Support\Collection;

class CreateAffinityRule extends TaskJob
{
    public AffinityRuleMember $affinityRuleMember;

    public function handle()
    {
        $this->affinityRuleMember = $this->task->resource;

        $memberCount = $this->affinityRuleMember->affinityRule->affinityRuleMembers()->count();

        if ($this->task->name == Sync::TASK_NAME_DELETE) {
            $memberCount -= 1;
        }

        if ($memberCount < 2) {
            $this->info('Affinity rules need at least two members', [
                'affinity_rule_id' => $this->affinityRuleMember->id,
                'member_count' => $this->affinityRuleMember->affinityRule->affinityRuleMembers()->count(),
            ]);
            return;
        }

        $hostGroupId = $this->affinityRuleMember->instance->getHostGroupId();
        $affinityRuleMembers = $this->affinityRuleMember->affinityRule->affinityRuleMembers()->get();
        $instanceIds = $affinityRuleMembers->filter(
            function (AffinityRuleMember $affinityRuleMember) use ($hostGroupId) {
                if ($affinityRuleMember->instance->getHostGroupId() == $hostGroupId) {
                    return $affinityRuleMember->instance->id;
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
        $availabilityZone = $this->affinityRuleMember->instance->availabilityZone;
        $uriEndpoint = ($this->affinityRuleMember->type == 'affinity') ?
            KingpinService::AFFINITY_URI :
            KingpinService::ANTI_AFFINITY_URI;

        try {
            $this->info('Creating Constraint', [
                'host_group_id' => $hostGroupId,
                'affinity_rule_id' => $this->affinityRuleMember->affinityRule->id,
                'vpc_id' => $this->affinityRuleMember->affinityRule->vpc->id,
            ]);
            $response = $availabilityZone->kingpinService()->post(
                sprintf($uriEndpoint, HostGroup::mapId($availabilityZone->id, $hostGroupId)),
                [
                    'json' => [
                        'ruleName' => $this->affinityRuleMember->affinityRule->id,
                        'vpcId' => $this->affinityRuleMember->affinityRule->vpc->id,
                        'instanceIds' => $instanceIds->toArray(),
                    ],
                ]
            );
        } catch (\Exception $e) {
            $this->info($e->getMessage(), [
                'affinity_rule_id' => $this->affinityRuleMember->affinityRule->id,
                'hostgroup_id' => $hostGroupId
            ]);
            throw $e;
        }
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to create rule');
        }
        return true;
    }
}
