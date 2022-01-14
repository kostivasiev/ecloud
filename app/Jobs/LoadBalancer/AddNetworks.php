<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\Job;
use App\Models\V2\LoadBalancer;
use App\Models\V2\LoadBalancerNetwork;
use App\Models\V2\Network;
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
            Log::info(get_class($this) . ': No networks to add, skipping');
            return;
        }

        if (empty($this->task->data['load_balancer_network_ids'])) {
            $data = $this->task->data;

            foreach ($this->task->data['network_ids'] as $networkId) {
                $network = Network::find($networkId);
                if (!$network) {
                    Log::warning(get_class($this) . ': Failed to load network to associate with load balancer: ' . $networkId, ['id' => $loadBalancer->id]);
                    continue;
                }

                Log::info(get_class($this) . ': Adding network ' . $network->id . ' to load balancer ' . $loadBalancer->id, ['id' => $loadBalancer->id]);

                $loadBalancerNetwork = app()->make(LoadBalancerNetwork::class);
                $loadBalancerNetwork->loadBalancer()->associate($loadBalancer);
                $loadBalancerNetwork->network()->associate($network);
                $loadBalancerNetwork->syncSave();

                $data['load_balancer_network_ids'][] = $loadBalancerNetwork->id;
            }
            $this->task->setAttribute('data', $data)->saveQuietly();
        }

        $this->awaitSyncableResources($this->task->data['load_balancer_network_ids']);
    }
}
