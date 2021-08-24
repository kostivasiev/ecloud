<?php

namespace App\Jobs\OrchestratorBuild;

use App\Jobs\Job;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateRouters extends Job
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

        if (!$data->has('routers')) {
            Log::info(get_class($this) . ' : OrchestratorBuild does not contain any routers, skipping', ['id' => $this->model->id]);
            return;
        }

        collect($data->get('routers'))->each(function ($definition, $index) use ($orchestratorBuild) {
            // Check if a resource has already been created
            if (isset($orchestratorBuild->state['router']) && isset($orchestratorBuild->state['router'][$index])) {
                Log::info(get_class($this) . ' : OrchestratorBuild router. ' . $index . ' has already been initiated, skipping', ['id' => $this->model->id]);
                return;
            }

            $definition = $orchestratorBuild->render($definition);

            $router = app()->make(Router::class);
            $router->fill($definition->only(['name', 'vpc_id', 'availability_zone_id', 'router_throughput_id'])->toArray());
            // Optional parameters : availability_zone_id & router_throughput_id
            if (!$definition->has('availability_zone_id')) {
                $vpc = Vpc::findorFail($definition->get('vpc_id'));
                $availabilityZone = $vpc->region()->first()->availabilityZones()->first();
                // As long as we set the AZ id here, the DefaultRouterThroughput listener can use that to set the default router throughput.
                $router->availabilityZone()->associate($availabilityZone);
            }

            $router->syncSave();

            Log::info(get_class($this) . ' : OrchestratorBuild created router ' . $router->id, ['id' => $this->model->id]);

            $orchestratorBuild->updateState('router', $index, $router->id);
        });
    }
}
