<?php

namespace App\Jobs\AffinityRuleMember;

use App\Jobs\TaskJob;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\AvailabilityZone;
use App\Services\V2\KingpinService;
use Illuminate\Support\Facades\Log;

class DeleteExistingRule extends TaskJob
{
    private AffinityRuleMember $model;
    private AvailabilityZone $availabilityZone;

    public function __construct($task)
    {
        parent::__construct($task);
        $this->model = $this->task->resource;
        $this->availabilityZone = $this->model->instance->availabilityZone;
    }

    public function handle()
    {
        $hostGroupId = $this->model->instance->getHostGroupId();
        if (!$hostGroupId) {
            $message = 'HostGroup could not be retrieved for instance ' . $this->model->instance->id;
            $this->fail($message);
            return;
        }

        if ($this->affinityRuleExists($hostGroupId)) {
            try {
                $response = $this->availabilityZone->kingpinService()->delete(
                    sprintf(KingpinService::DELETE_CONSTRAINT_URI, $hostGroupId, $this->model->affinityRule->id)
                );

                if ($response->getStatusCode() !== 200) {
                    $message = 'Failed to delete constraint ' . $this->model->id . ' on ' . $hostGroupId;
                    $this->fail(new \Exception($message));
                    return;
                }
            } catch (\Exception $e) {
                $this->fail($e);
                return;
            }
        }
    }

    public function affinityRuleExists(string $hostGroupId): bool
    {
        try {
            $response = $this->availabilityZone->kingpinService()
                ->get(
                    sprintf(KingpinService::GET_CONSTRAINT_URI, $hostGroupId)
                );
        } catch (\Exception $e) {
            $message = 'Failed to retrieve ' . $hostGroupId . ' : ' . $e->getMessage();
            Log::info($message);
            return false;
        }
        return collect(json_decode($response->getBody()->getContents(), true))
                ->where('ruleName', '=', $this->model->affinityRule->id)
                ->count() > 0;
    }
}
