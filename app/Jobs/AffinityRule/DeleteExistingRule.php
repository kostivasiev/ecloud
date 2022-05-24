<?php

namespace App\Jobs\AffinityRule;

use App\Jobs\Job;
use App\Models\V2\AffinityRule;
use App\Models\V2\HostGroup;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class DeleteExistingRule extends Job
{
    use Batchable, LoggableModelJob;

    private Task $task;
    private AffinityRule $model;

    public const GET_CONSTRAINT_URI = '/api/v2/hostgroup/%s/constraint';
    public const DELETE_CONSTRAINT_URI = '/api/v2/hostgroup/%s/constraint/%s';
    public array $existingRules = [];

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        $this->model
            ->vpc
            ->hostGroups()
            ->each(function (HostGroup $hostGroup) {
                if ($this->affinityRuleExists($hostGroup)) {
                    try {
                        $response = $hostGroup->availabilityZone->kingpinService()->delete(
                            sprintf(static::DELETE_CONSTRAINT_URI, $hostGroup->id, $this->model->id)
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
            });
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
                ->where('ruleName', '=', $this->model->id)
                ->count() > 0;
    }
}
