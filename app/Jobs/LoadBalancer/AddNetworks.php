<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\Job;
use App\Models\V2\LoadBalancer;
use App\Models\V2\Network;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use App\Models\V2\Task;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AddNetworks extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;
    
    private LoadBalancer $model;

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $loadBalancer = $this->model;

        if (empty($this->task->data['network_ids'])) {
            return;
        }

        if (empty($this->task->data['load_balancer_network_ids'])) {
            $loadBalancerNetworkIds = [];

            $networks = collect($this->task->data['network_ids'])
                ->map(fn($networkId) => !Network::find($networkId))
                ->filter();



            $networks->each(function ($network) use ($loadBalancer, &$loadBalancerNetworkIds) {
                Log::info(get_class($this) . ': Adding network ' . $network->id . ' to load balancer ' . $loadBalancer->id, ['id' => $loadBalancer->id]);

                $instanceSoftware = app()->make(InstanceSoftware::class);
                $instanceSoftware->name = $software->name;
                $instanceSoftware->instance()->associate($instance);
                $instanceSoftware->software()->associate($software);
                $instanceSoftware->syncSave();

                $instanceSoftwareIds[] = $instanceSoftware->id;
            });


            $this->task->setAttribute('data', $data)->saveQuietly();
        } else {
            $this->awaitSyncableResources($this->task->data['load_balancer_network_ids']);
        }

}
