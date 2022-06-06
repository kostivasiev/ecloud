<?php

namespace App\Jobs\AffinityRuleMember;

use App\Jobs\TaskJob;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\HostGroup;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitRuleDeletion extends TaskJob
{
    public AffinityRuleMember $model;

    public int $backoff = 5;

    public const GET_CONSTRAINT_URI = '/api/v2/hostgroup/%s/constraint';

    public function __construct($task)
    {
        parent::__construct($task);
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        if (empty($this->task->data['existing_rules'])) {
            Log::info('No rules to delete, skipping', [
                'affinity_rule_id' => $this->model->id,
            ]);
            return;
        }
        $existingRules = $this->task->data['existing_rules'];
        foreach ($existingRules as $hostGroupId) {
            if ($this->affinityRuleExists($hostGroupId)) {
                Log::info('Rule deletion not complete, waiting', [
                    'affinity_rule_id' => $this->model->id,
                    'host_group_id' => $hostGroupId
                ]);
                $this->release($this->backoff);
                return;
            }
        }
        Log::info('Rule deletion complete', [
            'affinity_rule_id' => $this->model->affinityRule->id,
        ]);
    }

    public function affinityRuleExists(string $hostGroupId): bool
    {
        $hostGroup = HostGroup::find($hostGroupId);
        if ($hostGroup) {
            try {
                $response = $hostGroup->availabilityZone->kingpinService()
                    ->get(
                        sprintf(static::GET_CONSTRAINT_URI, $hostGroup->id),
                        [
                            'headers' => [
                                'X-MOCK-RULE-MEMBER' => $this->model->id,
                            ],
                        ]
                    );
            } catch (\Exception $e) {
                Log::info($e->getMessage());
                Log::info('Contraints not found for hostgroup', [
                    'host_group_id' => $hostGroup->id,
                ]);
                return false;
            }
            return collect(json_decode($response->getBody()->getContents(), true))
                    ->where('ruleName', '=', $this->model->id)
                    ->count() > 0;
        }
        return false;
    }
}
