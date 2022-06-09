<?php

namespace App\Jobs\AffinityRuleMember;

use App\Jobs\TaskJob;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\HostGroup;
use App\Services\V2\KingpinService;

class AwaitRuleDeletion extends TaskJob
{
    public AffinityRuleMember $affinityRuleMember;

    public int $backoff = 5;

    public function handle()
    {
        $this->affinityRuleMember = $this->task->resource;
        $instance = $this->affinityRuleMember->instance;
        $hostGroupId = $instance->getHostGroupId();
        if (!$hostGroupId) {
            $message = 'HostGroup could not be retrieved for instance ' . $instance->id;
            $this->fail(new \Exception($message));
            return;
        }

        if ($instance->hasAffinityRule($hostGroupId, $this->affinityRuleMember->affinityRule->id)) {
            $this->info('Rule deletion not complete, waiting', [
                'affinity_rule_id' => $this->affinityRuleMember->affinityRule->id,
                'host_group_id' => $hostGroupId
            ]);
            $this->release($this->backoff);
            return;
        }

        $this->info('Rule deletion complete', [
            'affinity_rule_id' => $this->affinityRuleMember->affinityRule->id,
        ]);
    }
}
