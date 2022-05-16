<?php

namespace App\Jobs\AffinityRule;

use App\Jobs\Job;
use App\Models\V2\AffinityRule;
use App\Models\V2\HostGroup;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitRuleDeletion extends Job
{
    use Batchable, LoggableModelJob;

    public Task $task;
    public AffinityRule $model;

    public int $backoff = 5;

    public const GET_CONSTRAINT_URI = '/api/v2/hostgroup/%s/constraint';

    public function __construct(Task $task)
    {
        $this->task = $task;
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
            'affinity_rule_id' => $this->model->id,
        ]);
    }

    public function affinityRuleExists(string $hostGroupId): bool
    {
        $hostGroup = HostGroup::find($hostGroupId);
        if ($hostGroup) {
            try {
                $response = $hostGroup->availabilityZone->kingpinService()
                    ->get(sprintf(static::GET_CONSTRAINT_URI, $hostGroup->id));
            } catch (\Exception $e) {
                return false;
            }
            return collect(json_decode($response->getBody()->getContents(), true))
                    ->where('ruleName', '=', $this->model->id)
                    ->count() > 0;
        }
        return false;
    }
}
