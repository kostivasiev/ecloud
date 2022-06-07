<?php

namespace App\Jobs\AffinityRuleMember;

class AwaitRuleCreation extends AwaitRuleDeletion
{
    public function handle()
    {
        $this->affinityRuleMember = $this->task->resource;
        $hostGroupId = $this->affinityRuleMember->instance->getHostGroupId();
        if (!$this->affinityRuleExists($hostGroupId)) {
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
