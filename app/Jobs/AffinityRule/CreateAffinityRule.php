<?php

namespace App\Jobs\AffinityRule;

use App\Jobs\Job;
use App\Models\V2\AffinityRule;
use App\Models\V2\HostGroup;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateAffinityRule extends Job
{
    use Batchable, LoggableModelJob;

    public const ANTI_AFFINITY_URI = '/api/v2/hostgroup/%s/constraint/instance/separate';
    public const AFFINITY_URI = '/api/v2/hostgroup/%s/constraint/instance/keep-together';

    private Task $task;
    private AffinityRule $model;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        if ($this->model->affinityRuleMembers()->count() <= 0) {
            Log::info('Rule has no members, skipping', [
                'affinity_rule_id' => $this->model->id,
            ]);
            return;
        }

        if ($this->model->affinityRuleMembers()->count() < 2) {
            Log::info('Affinity rules need at least two members', [
                'affinity_rule_id' => $this->model->id,
                'member_count' => $this->model->affinityRuleMembers()->count(),
            ]);
            return;
        }

        $instanceIds = $this->model->affinityRuleMembers()->get()
            ->pluck('instance_id')
            ->toArray();

        $this->model->vpc->hostGroups()->each(function (HostGroup $hostGroup) use ($instanceIds) {
            $this->createAffinityRule($hostGroup, $instanceIds);
        });
    }

    public function createAffinityRule(HostGroup $hostGroup, array $instanceIds)
    {
        $uriEndpoint = ($this->model->type == 'affinity') ?
            static::AFFINITY_URI :
            static::ANTI_AFFINITY_URI;

        try {
            $response = $hostGroup->availabilityZone->kingpinService()->post(
                sprintf($uriEndpoint, $hostGroup->id),
                [
                    'json' => [
                        'ruleName' => $this->model->id,
                        'vpcId' => $this->model->vpc->id,
                        'instanceIds' => $instanceIds,
                    ],
                ]
            );
            if ($response->getStatusCode() == 200) {
                $createdRules = [];
                if (isset($this->task->data['created_rules'])) {
                    $createdRules = $this->task->data['created_rules'];
                }
                $createdRules[] = $hostGroup->id;
                $this->task->updateData('created_rules', $createdRules);
                return true;
            } else {
                $this->fail(new \Exception('Failed to create rule'));
            }
        } catch (\Exception $e) {
            Log::info('Failed to create affinity rule', [
                'affinity_rule_id' => $this->model->id,
                'hostgroup_id' => $hostGroup->id,
                'message' => $e->getMessage(),
            ]);
            $this->fail($e);
        }
    }
}
