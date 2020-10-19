<?php

namespace App\Jobs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Imtigger\LaravelJobStatus\Trackable;

abstract class TaskJob extends \App\Jobs\Job {
    use Trackable;

    public function __construct(Model $resource)
    {
        $this->prepareStatus();
        $this->update(['resource_id' => $resource->getKey()]);
    }

    public function dispatchChild($job) {
        Log::info("BEFORE: " . print_r($job));
        dispatch($job)->onConnection('sync');
        Log::info("AFTER: " . print_r($job));
/*        if ($job->hasFailed()) {
            $this->fail(new \Exception("Child job failed: " . $job->exception()));
        }*/
    }

    public function dispatchChildren(array $jobs) {
        foreach ($jobs as $job) {
            $this->dispatchChild($job);
        }
    }
}