<?php

namespace App\Jobs\AffinityRuleMember;

use App\Jobs\Job;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\HostGroup;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateAffinityRule extends Job
{
    use Batchable, LoggableModelJob;

    private Task $task;
    private AffinityRuleMember $model;

    public const ANTI_AFFINITY_URI = '/api/v2/hostgroup/%s/constraint/instance/separate';
    public const AFFINITY_URI = '/api/v2/hostgroup/%s/constraint/instance/keep-together';
    public const GET_HOSTGROUP_URI = '/api/v2/vpc/%s/instance/%s';

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        $hostGroups = [];
        if ($this->model->affinityRule->affinityRuleMembers()->count() <= 0) {
            Log::info('Rule has no members, skipping', [
                'affinity_rule_id' => $this->model->id,
            ]);
            return;
        }

        if ($this->model->affinityRule->affinityRuleMembers()->count() < 2) {
            Log::info('Affinity rules need at least two members', [
                'affinity_rule_id' => $this->model->id,
                'member_count' => $this->model->affinityRule->affinityRuleMembers()->count(),
            ]);
            return;
        }

        $instanceIds = $this->model->affinityRule->affinityRuleMembers()->get()
            ->pluck('instance_id')
            ->toArray();

        try {
            $hostGroup = $this->getHostGroup($this->model->instance);
            $this->createAffinityRule($hostGroup, $instanceIds);
        } catch (\Exception $e) {
            $this->fail($e);
            return;
        }
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
                        'ruleName' => $this->model->affinityRule->id,
                        'vpcId' => $this->model->affinityRule->vpc->id,
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
                throw new \Exception('Failed to create rule');
            }
        } catch (\Exception $e) {
            Log::info('Failed to create affinity rule', [
                'affinity_rule_id' => $this->model->affinityRule->id,
                'hostgroup_id' => $hostGroup->id,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * @param Instance $instance
     * @return HostGroup|null
     * @throws \Exception
     */
    public function getHostGroup(Instance $instance): ?HostGroup
    {
        if ($instance->hostGroup !== null) {
            return $instance->hostGroup;
        }

        try {
            $response = $instance->availabilityZone
                ->kingpinService()
                ->get(
                    sprintf(static::GET_HOSTGROUP_URI, $instance->vpc->id, $instance->id)
                );
        } catch (\Exception $e) {
            Log::info('Unable to retrieve hostgroup data for instance', [
                'instance_id' => $instance->id,
                'vpc_id' => $instance->vpc->id,
            ]);
            throw $e;
        }
        $hostGroup = HostGroup::find(
            (json_decode($response->getBody()->getContents()))->hostGroupID
        );
        if (!$hostGroup) {
            throw new \Exception(
                sprintf(
                    'Hostgroup %s could not be found',
                    (json_decode($response->getBody()->getContents()))->hostGroupID
                )
            );
        }
        return $hostGroup;
    }
}
