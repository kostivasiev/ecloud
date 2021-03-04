<?php

namespace App\Jobs\NetworkPolicy;

use App\Jobs\Job;
use Illuminate\Support\Facades\Log;

class DeleteChildResources extends Job
{
    protected $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $this->model->networkRules->each(function ($networkRule) {
            $networkRule->networkRulePorts->each(function ($networkRulePort) {
                $networkRulePort->delete();
            });
            $networkRule->delete();
        });

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function failed($exception)
    {
        $this->model->setSyncFailureReason($exception->getMessage());
    }
}
