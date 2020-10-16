<?php

namespace App\Jobs;

use Illuminate\Database\Eloquent\Model;
use Imtigger\LaravelJobStatus\Trackable;

abstract class TaskJob extends \App\Jobs\Job {
    use Trackable;

    public function __construct(Model $resource)
    {
        $this->prepareStatus();
        $this->update(['resource_id' => $resource->getKey()]);
    }
}