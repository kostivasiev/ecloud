<?php

namespace App\Jobs\AffinityRule;

use App\Jobs\Job;
use App\Models\V2\AffinityRule;
use App\Models\V2\Task;

class AffinityRuleJob extends Job
{
    public function __construct(Task $task)
    {
        $this->task = $task;
        if ($this->task->resource instanceof AffinityRule) {
            $this->model = $this->task->resource;
        } elseif ($this->task->resource->affinityRule instanceof AffinityRule) {
            $this->model = $this->task->resource->affinityRule;
        } else {
            $this->fail(sprintf('Affinity Rule not found for member %s', $this->task->resource->id));
        }
    }
}