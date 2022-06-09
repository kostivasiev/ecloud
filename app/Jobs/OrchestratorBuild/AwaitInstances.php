<?php

namespace App\Jobs\OrchestratorBuild;

use App\Jobs\Job;
use App\Models\V2\OrchestratorBuild;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitInstances extends Job
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
        if (!$state->has('instance')) {
            Log::info(get_class($this) . ' : No Instances detected in build state, skipping', ['id' => $this->model->id]);
            return;
        }

        $instanceIds = [];
        foreach ($state->get('instance') as $instanceState) {
            $instanceIds = [...$instanceIds, ...iterator_to_array(
                new \RecursiveIteratorIterator(
                    new \RecursiveArrayIterator($instanceState)
                )
            )];
        }

        $this->awaitSyncableResources($instanceIds); // need to rework this to get instance ids
    }
}
