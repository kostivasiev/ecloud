<?php

namespace App\Jobs\OrchestratorBuild;

use App\Jobs\Job;
use App\Models\V2\Network;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CreateNetworks extends Job
{
    use Batchable, LoggableModelJob;

    private OrchestratorBuild $model;

    public function __construct(OrchestratorBuild $orchestratorBuild)
    {
        $this->model = $orchestratorBuild;
    }

    public function handle()
    {
        $orchestratorBuild = $this->model;

        $data = collect(json_decode($orchestratorBuild->orchestratorConfig->data));

        if (!$data->has('networks')) {
            Log::info(get_class($this) . ' : OrchestratorBuild does not contain any networks, skipping', ['id' => $this->model->id]);
            return;
        }

        collect($data->get('networks'))->each(function ($definition, $index) use ($orchestratorBuild) {
            // Check if a resource has already been created
            if (isset($orchestratorBuild->state['network']) && isset($orchestratorBuild->state['network'][$index])) {
                Log::info(get_class($this) . ' : OrchestratorBuild network. ' . $index . ' has already been initiated, skipping', ['id' => $this->model->id]);
                return;
            }

            $definition = $orchestratorBuild->render($definition);

            $network = app()->make(Network::class);
            // Optional parameters : subnet, added by listener on 'Creating' event
            $network->fill($definition->only(['name', 'router_id', 'subnet'])->toArray());

            $network->syncSave();

            Log::info(get_class($this) . ' : OrchestratorBuild created network ' . $network->id, ['id' => $this->model->id]);

            $orchestratorBuild->updateState('network', $index, $network->id);
        });
    }
}
