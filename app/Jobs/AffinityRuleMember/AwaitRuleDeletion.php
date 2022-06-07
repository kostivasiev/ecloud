<?php

namespace App\Jobs\AffinityRuleMember;

use App\Jobs\TaskJob;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\HostGroup;
use App\Models\V2\Task;
use App\Services\V2\KingpinService;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitRuleDeletion extends TaskJob
{
    public AffinityRuleMember $affinityRuleMember;

    public int $backoff = 5;

    public function __construct($task)
    {
        parent::__construct($task);
        $this->affinityRuleMember = $this->task->resource;
    }

    public function handle()
    {
        $hostGroupId = $this->affinityRuleMember->instance->getHostGroupId();
        if ($this->affinityRuleExists($hostGroupId)) {
            $this->info('Rule deletion not complete, waiting', [
                'affinity_rule_id' => $this->affinityRuleMember->id,
                'host_group_id' => $hostGroupId
            ]);
            $this->release($this->backoff);
            return;
        }

        $this->info('Rule deletion complete', [
            'affinity_rule_id' => $this->affinityRuleMember->affinityRule->id,
        ]);
    }

    public function affinityRuleExists(?string $hostGroupId): bool
    {
        if ($hostGroupId === null) {
            return false;
        }
        $hostGroup = HostGroup::find($hostGroupId);
        if ($hostGroup) {
            try {
                $response = $hostGroup->availabilityZone->kingpinService()
                    ->get(
                        sprintf(KingpinService::GET_CONSTRAINT_URI, $hostGroup->id)
                    );
            } catch (\Exception $e) {
                $this->info($e->getMessage());
                $this->info('Contraints not found for hostgroup', [
                    'host_group_id' => $hostGroup->id,
                ]);
                return false;
            }
            return collect(json_decode($response->getBody()->getContents(), true))
                    ->where('ruleName', '=', $this->affinityRuleMember->id)
                    ->count() > 0;
        }
        return false;
    }
}
