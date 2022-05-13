<?php

namespace App\Jobs\AffinityRule;

use App\Jobs\Job;
use App\Models\V2\AffinityRule;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\HostGroup;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateAffinityRule extends Job
{
    use Batchable, LoggableModelJob;

    private Task $task;
    private AffinityRule $model;

    public const GET_CONSTRAINT_URI = '/api/v2/hostgroup/%s/constraint';
    public const ANTI_AFFINITY_URI = '/api/v2/hostgroup/%s/constraint/instance/separate';
    public const AFFINITY_URI = '/api/v2/hostgroup/%s/constraint/instance/keep-together';

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

        $instanceIds = $this->model->affinityRuleMembers()->get()
            ->pluck('instance_id')
            ->toArray();

        $this->model->vpc->hostGroups()->each(function (HostGroup $hostGroup) use ($instanceIds) {
            if (!$this->affinityRuleExists($hostGroup)) {
                $this->createAffinityRule($hostGroup, $instanceIds);
            }
        });
    }

    public function affinityRuleExists(HostGroup $hostGroup)
    {
        try {
            $response = $hostGroup->availabilityZone->kingpinService()
                ->get(sprintf(static::GET_CONSTRAINT_URI, $hostGroup->id));
        } catch (\Exception $e) {
            return false;
        }
        return collect($response->getBody()->getContents())
                ->where('name', '=', $this->model->id)
                ->count() > 0;
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
