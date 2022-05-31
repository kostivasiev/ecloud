<?php

namespace App\Jobs\AffinityRuleMember;

use App\Jobs\Job;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\HostGroup;
use App\Models\V2\Task;
use App\Services\V2\KingpinService;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class DeleteExistingRule extends Job
{
    use Batchable, LoggableModelJob;

    private Task $task;
    private AffinityRuleMember $model;
    public array $existingRules = [];

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        $hostGroup = $this->model->instance->getHostGroup();
        if (!$hostGroup) {
            $message = 'HostGroup could not be retrieved for instance ' . $this->model->instance->id;
            $this->fail($message);
            return;
        }

        if ($this->affinityRuleExists($hostGroup)) {
            try {
                $response = $hostGroup->availabilityZone->kingpinService()->delete(
                    sprintf(KingpinService::DELETE_CONSTRAINT_URI, $hostGroup->id, $this->model->affinityRule->id)
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
                ->get(
                    sprintf(KingpinService::GET_CONSTRAINT_URI, $hostGroup->id),
                    [
                        'body' => [
                            'X-MOCK-RULE-NAME' => $this->model->id
                        ]
                    ]
                );
        } catch (\Exception $e) {
            return false;
        }
        return collect(json_decode($response->getBody()->getContents(), true))
                ->where('ruleName', '=', $this->model->affinityRule->id)
                ->count() > 0;
    }
}
