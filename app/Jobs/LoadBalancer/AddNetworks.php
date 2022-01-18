<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\TaskJob;
use App\Models\V2\LoadBalancerNetwork;
use App\Models\V2\Network;
use App\Traits\V2\TaskJobs\AwaitResources;
use Illuminate\Support\Facades\Log;

class AddNetworks extends TaskJob
{
    use AwaitResources;

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $loadBalancer = $this->task->resource;

        if (empty($this->task->data['network_ids'])) {
            $this->info('No networks to add, skipping');
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

        if (isset($this->task->data['load_balancer_network_ids'])) {
            $this->awaitSyncableResources($this->task->data['load_balancer_network_ids']);
        }
    }
}
