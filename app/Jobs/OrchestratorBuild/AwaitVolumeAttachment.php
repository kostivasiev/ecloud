<?php

namespace App\Jobs\OrchestratorBuild;

use App\Jobs\Job;
use App\Models\V2\OrchestratorBuild;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitVolumeAttachment extends Job
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
        if (!$state->has('instance_volume')) {
            Log::info(get_class($this) . ' : No Volumes pending attachment, skipping', ['id' => $this->model->id]);
            return;
        }

        $this->awaitSyncableResources($state->get('instance_volume'));
    }
}
