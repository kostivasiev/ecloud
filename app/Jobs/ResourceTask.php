<?php

namespace App\Jobs;

use Imtigger\LaravelJobStatus\Trackable;

abstract class ResourceTask extends \App\Jobs\Job {
    use Trackable;

    public function __construct($resourceId)
    {
        $this->prepareStatus();
        $this->update(['resource_id' => $resourceId]);
    }
}