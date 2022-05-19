<?php

namespace App\Jobs\AffinityRuleMember;

use App\Jobs\Job;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\HostGroup;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class DeleteExistingRule extends Job
{
    use Batchable, LoggableModelJob;

    private Task $task;
    private AffinityRuleMember $model;

    public const GET_CONSTRAINT_URI = '/api/v2/hostgroup/%s/constraint';
    public const DELETE_CONSTRAINT_URI = '/api/v2/hostgroup/%s/constraint/%s';
    public const GET_HOSTGROUP_URI = '/api/v2/vpc/%s/instance/%s';
    public array $existingRules = [];

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        try {
            $hostGroup = $this->getHostGroup($this->model->instance);
        } catch (\Exception $e) {
            $this->fail($e);
            return;
        }

        if ($this->affinityRuleExists($hostGroup)) {
            try {
                $response = $hostGroup->availabilityZone->kingpinService()->delete(
                    sprintf(static::DELETE_CONSTRAINT_URI, $hostGroup->id, $this->model->affinityRule->id)
                );
                $this->existingRules[] = $hostGroup->id;

                if ($response->getStatusCode() !== 200) {
                    $message = 'Failed to delete constraint ' . $this->model->id . ' on ' . $hostGroup->id;
                    $this->fail(new \Exception($message));
                    return;
                }
            } catch (\Exception $e) {
                $this->fail($e);
                return;
            }
        }
        if (!empty($this->existingRules)) {
            $this->task->updateData('existing_rules', $this->existingRules);
        }
    }

    public function affinityRuleExists(HostGroup $hostGroup): bool
    {
        try {
            $response = $hostGroup->availabilityZone->kingpinService()
                ->get(sprintf(static::GET_CONSTRAINT_URI, $hostGroup->id));
        } catch (\Exception $e) {
            return false;
        }
        return collect(json_decode($response->getBody()->getContents(), true))
                ->where('ruleName', '=', $this->model->affinityRule->id)
                ->count() > 0;
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
            throw $e;
        }
        $hostGroup = HostGroup::find(
            (json_decode($response->getBody()->getContents()))->hostGroupID
        );
        if (!$hostGroup) {
            throw new \Exception(
                sprintf('Hostgroup %s could not be found', $hostGroupId)
            );
        }
        return $hostGroup;
    }
}
