<?php

namespace App\Jobs\AffinityRule;

use Illuminate\Support\Facades\Log;

class AwaitRuleCreation extends AwaitRuleDeletion
{
    public function handle()
    {
        if (empty($this->task->data['created_rules'])) {
            Log::info('No rules to create, skipping', [
                'affinity_rule_id' => $this->model->id,
            ]);
            return;
        }
        $createdRules = $this->task->data['created_rules'];
        foreach ($createdRules as $hostGroupId) {
            if (!$this->affinityRuleExists($hostGroupId)) {
                Log::info('Rule creation not complete, waiting', [
                    'affinity_rule_id' => $this->model->id,
                    'host_group_id' => $hostGroupId
                ]);
                $this->release($this->backoff);
                return;
            }
        }
        Log::info('Rule creation complete', [
            'affinity_rule_id' => $this->model->id,
        ]);
    }
}
