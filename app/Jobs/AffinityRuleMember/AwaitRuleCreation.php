<?php

namespace App\Jobs\AffinityRuleMember;

class AwaitRuleCreation extends AwaitRuleDeletion
{
    public function handle()
    {
        $this->affinityRuleMember = $this->task->resource;
        $instance = $this->affinityRuleMember->instance;
        $hostGroupId = $instance->getHostGroupId();
        if (!$hostGroupId) {
            $message = 'HostGroup could not be retrieved for instance ' . $instance->id;
            $this->fail($message);
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
