<?php

namespace App\Jobs\NetworkPolicy;

use App\Jobs\Job;
use App\Models\V2\NetworkPolicy;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class DeleteChildResources extends Job
{
    use Batchable, LoggableModelJob;

    private NetworkPolicy $model;

    public function __construct(NetworkPolicy $networkPolicy)
    {
        $this->model = $networkPolicy;
    }

    public function handle()
    {
        // TODO: do we want to do this without events?
        $this->model->networkRules->each(function ($networkRule) {
            $networkRule->networkRulePorts->each(function ($networkRulePort) {
                $networkRulePort->delete();
            });
            $networkRule->delete();
        });
    }
}
