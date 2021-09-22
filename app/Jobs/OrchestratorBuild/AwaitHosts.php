<?php

namespace App\Jobs\OrchestratorBuild;

use App\Jobs\Job;
use App\Models\V2\OrchestratorBuild;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitHosts extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;

    private OrchestratorBuild $model;

    public function __construct(OrchestratorBuild $orchestratorBuild)
    {
        $this->model = $orchestratorBuild;
    }

    public function handle()
    {
        $orchestratorBuild = $this->model;

        $state = collect($orchestratorBuild->state);
        if (!$state->has('host')) {
            Log::info(get_class($this) . ' : No Hosts detected in build state, skipping', ['id' => $this->model->id]);
            return;
        }

        $this->awaitSyncableResources($state->get('host'));
    }
}