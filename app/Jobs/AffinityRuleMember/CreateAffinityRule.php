<?php

namespace App\Jobs\AffinityRuleMember;

use App\Jobs\TaskJob;
use App\Models\V2\AffinityRuleMember;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CreateAffinityRule extends TaskJob
{
    use Batchable, LoggableModelJob;

    private AffinityRuleMember $model;

    public const ANTI_AFFINITY_URI = '/api/v2/hostgroup/%s/constraint/instance/separate';
    public const AFFINITY_URI = '/api/v2/hostgroup/%s/constraint/instance/keep-together';
    public const GET_HOSTGROUP_URI = '/api/v2/vpc/%s/instance/%s';

    public function __construct($task)
    {
        parent::__construct($task);
        $this->model = $this->task->resource;
    }

    public function handle()
    {
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

        $hostGroupId = $this->model->instance->getHostGroupId();
        $affinityRuleMembers = $this->model->affinityRule->affinityRuleMembers()->get();
        $instanceIds = $affinityRuleMembers->filter(
            function (AffinityRuleMember $affinityRuleMember) use ($hostGroupId) {
                if ($affinityRuleMember->instance->id !== $this->model->instance->id) {
                    if ($affinityRuleMember->instance->getHostGroupId() == $hostGroupId) {
                        return $affinityRuleMember->instance->id;
                    }
                }
            }
        )->pluck('instance_id');

        try {
            $this->createAffinityRule($hostGroupId, $instanceIds);
        } catch (\Exception $e) {
            $this->fail($e);
            return;
        }
    }

    public function createAffinityRule(string $hostGroupId, Collection $instanceIds)
    {
        $availabilityZone = $this->model->instance->availabilityZone;
        $uriEndpoint = ($this->model->type == 'affinity') ?
            static::AFFINITY_URI :
            static::ANTI_AFFINITY_URI;

        try {
            $response = $availabilityZone->kingpinService()->post(
                sprintf($uriEndpoint, $hostGroupId),
                [
                    'json' => [
                        'ruleName' => $this->model->affinityRule->id,
                        'vpcId' => $this->model->affinityRule->vpc->id,
                        'instanceIds' => $instanceIds,
                    ],
                ]
            );
        } catch (\Exception $e) {
            Log::info('Failed to create affinity rule', [
                'affinity_rule_id' => $this->model->affinityRule->id,
                'hostgroup_id' => $hostGroupId,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
        if ($response->getStatusCode() == 200) {
            $createdRules = [];
            if (isset($this->task->data['created_rules'])) {
                $createdRules = $this->task->data['created_rules'];
            }
            $createdRules[] = $hostGroupId;
            $this->task->updateData('created_rules', $createdRules);
            return true;
        } else {
            throw new \Exception('Failed to create rule');
        }
        return false;
    }
}
