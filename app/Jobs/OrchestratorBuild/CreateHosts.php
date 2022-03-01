<?php

namespace App\Jobs\OrchestratorBuild;

use App\Jobs\Job;
use App\Models\V2\Host;
use App\Models\V2\OrchestratorBuild;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateHosts extends Job
{
    use Batchable, LoggableModelJob;

    private OrchestratorBuild $model;

    public function __construct(OrchestratorBuild $orchestratorBuild)
    {
        $this->model = $orchestratorBuild;
    }

    public function handle()
    {
        // added the refresh to ensure that the model is up-to-date with host additions (if present)
        $orchestratorBuild = $this->model->refresh();
        $data = collect(json_decode($orchestratorBuild->orchestratorConfig->data));

        if (!$data->has('hosts')) {
            Log::info(get_class($this) . ' : OrchestratorBuild does not contain any Hosts, skipping', ['id' => $this->model->id]);
            return;
        }

        collect($data->get('hosts'))->each(function ($definition, $index) use ($orchestratorBuild) {
            // Check if a resource has already been created
            if (isset($orchestratorBuild->state['host']) && isset($orchestratorBuild->state['host'][$index])) {
                Log::info(get_class($this) . ' : OrchestratorBuild host. ' . $index . ' has already been initiated, skipping', ['id' => $this->model->id]);
                return;
            }

            $definition = $orchestratorBuild->render($definition);

            $host = app()->make(Host::class);
            $host->fill($definition->only(['name', 'host_group_id'])->toArray());
            $host->syncSave();

            Log::info(get_class($this) . ' : OrchestratorBuild created Host ' . $host->id, ['id' => $this->model->id]);

            $orchestratorBuild->updateState('host', $index, $host->id);
        });
    }
}
