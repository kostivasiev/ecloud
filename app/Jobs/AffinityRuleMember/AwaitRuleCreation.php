<?php

namespace App\Jobs\AffinityRuleMember;

use App\Support\Sync;

class AwaitRuleCreation extends AwaitRuleDeletion
{
    public function handle()
    {
        $this->affinityRuleMember = $this->task->resource;

        $memberCount = $this->affinityRuleMember->affinityRule->affinityRuleMembers()->count();

        if ($this->task->name == Sync::TASK_NAME_DELETE) {
            $memberCount -= 1;
        }

        if ($memberCount < 2) {
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
