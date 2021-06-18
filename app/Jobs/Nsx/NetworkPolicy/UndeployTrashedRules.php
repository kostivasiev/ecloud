<?php

namespace App\Jobs\Nsx\NetworkPolicy;

use App\Jobs\Job;
use App\Models\V2\NetworkPolicy;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UndeployTrashedRules extends Job
{
    use Batchable, LoggableModelJob;

    const RETRY_DELAY = 5;

    public $tries = 500;

    private $model;

    public function __construct(NetworkPolicy $networkPolicy)
    {
        $this->model = $networkPolicy;
    }

    public function handle()
    {
        $availabilityZone = $this->model->network->router->availabilityZone;

        $rulesResponse = $availabilityZone->nsxService()->get(
            '/policy/api/v1/infra/domains/default/security-policies/' . $this->model->id . '/rules'
        );
        $rulesResponseBody = json_decode($rulesResponse->getBody()->getContents());
        $rulesToRemove = [];

        foreach ($rulesResponseBody->results as $result) {
            $trashedRule = $this->model->networkRules()->withTrashed()->find($result->id);
            if ($trashedRule != null && $trashedRule->trashed()) {
                $rulesToRemove[] = $result->id;
            }
        }

        if (count($rulesToRemove) < 1) {
            Log::debug('No rules to remove, skipping');
        }

        foreach ($rulesToRemove as $ruleToRemove) {
            Log::debug("Removing network rule", ['ruleToRemove' => $ruleToRemove]);

            $availabilityZone->nsxService()->delete(
                '/policy/api/v1/infra/domains/default/security-policies/' . $this->model->id . '/rules/' . $ruleToRemove
            );
        }
    }
}
