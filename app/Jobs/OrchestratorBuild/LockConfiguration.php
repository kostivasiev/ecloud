<?php

namespace App\Jobs\OrchestratorBuild;

use App\Jobs\Job;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class LockConfiguration extends Job
{
    use Batchable, LoggableModelJob;

    private OrchestratorConfig $model;

    public function __construct(OrchestratorBuild $orchestratorBuild)
    {
        $this->model = $orchestratorBuild->orchestratorConfig;
    }

    public function handle()
    {
        $this->model->locked = true;
        $this->model->saveQuietly();
    }
}
