<?php

namespace App\Jobs\AffinityRuleMember;

class AwaitRuleCreation extends AwaitRuleDeletion
{
    public function handle()
    {
        $this->affinityRuleMember = $this->task->resource;
        if ($this->affinityRuleMember->affinityRule->affinityRuleMembers()->count() < 2) {
            $this->info('Affinity rules need at least two members, skipping', [
                'affinity_rule_id' => $this->affinityRuleMember->id,
                'member_count' => $this->affinityRuleMember->affinityRule->affinityRuleMembers()->count(),
            ]);
            return;
        }

        $instance = $this->affinityRuleMember->instance;
        $hostGroupId = $instance->getHostGroupId();
        if (!$hostGroupId) {
            $message = 'HostGroup could not be retrieved for instance ' . $instance->id;
            $this->fail(new \Exception($message));
            return;
        }

        if (!$instance->hasAffinityRule($hostGroupId, $this->affinityRuleMember->affinityRule->id)) {
            $this->info('Rule creation not complete, waiting', [
                'affinity_rule_id' => $this->affinityRuleMember->affinityRule->id,
                'host_group_id' => $hostGroupId
            ]);
            $this->release($this->backoff);
            return;
        }
        $this->info('Rule creation complete', [
            'affinity_rule_id' => $this->affinityRuleMember->affinityRule->id,
        ]);
    }
}
