<?php

namespace App\Jobs\NetworkPolicy;

use App\Jobs\Job;
use App\Models\V2\NetworkPolicy;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeleteChildResources extends Job
{
    use Batchable;

    private NetworkPolicy $networkPolicy;

    public function __construct(NetworkPolicy $networkPolicy)
    {
        $this->networkPolicy = $networkPolicy;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->networkPolicy->id]);

        // TODO: do we want to do this without events?
        $this->networkPolicy->networkRules->each(function ($networkRule) {
            $networkRule->networkRulePorts->each(function ($networkRulePort) {
                $networkRulePort->delete();
            });
            $networkRule->delete();
        });

        Log::info(get_class($this) . ' : Finished', ['id' => $this->networkPolicy->id]);
    }
}
