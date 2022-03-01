<?php

namespace App\Jobs\OrchestratorBuild;

use App\Jobs\Job;
use App\Models\V2\LoadBalancer;
use App\Models\V2\OrchestratorBuild;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateLoadBalancers extends Job
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

        if (!$data->has('load-balancers')) {
            Log::info(get_class($this) . ' : OrchestratorBuild does not contain any load balancers, skipping', ['id' => $this->model->id]);
            return;
        }

        collect($data->get('load-balancers'))->each(function ($definition, $index) use ($orchestratorBuild) {
            // Check if a resource has already been created
            if (isset($orchestratorBuild->state['load_balancer']) && isset($orchestratorBuild->state['load_balancer'][$index])) {
                Log::info(get_class($this) . ' : OrchestratorBuild load balancer. ' . $index . ' has already been initiated, skipping', ['id' => $this->model->id]);
                return;
            }

            $definition = $orchestratorBuild->render($definition);

            $loadBalancer = app()->make(LoadBalancer::class);
            $loadBalancer->fill($definition->only(['name', 'vpc_id', 'availability_zone_id', 'load_balancer_spec_id'])->toArray());
            $loadBalancer->syncSave();

            Log::info(get_class($this) . ' : OrchestratorBuild created load balancer ' . $loadBalancer->id, ['id' => $this->model->id]);

            $orchestratorBuild->updateState('load_balancer', $index, $loadBalancer->id);
        });
    }
}
