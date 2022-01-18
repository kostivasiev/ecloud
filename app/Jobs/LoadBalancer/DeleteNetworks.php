<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\TaskJob;
use App\Traits\V2\TaskJobs\AwaitResources;
use Illuminate\Support\Facades\Log;

class DeleteNetworks extends TaskJob
{
    use AwaitResources;

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $loadBalancer = $this->task->resource;

        if ($loadBalancer->loadBalancerNetworks()->count() == 0) {
            return;
        }

        if (empty($this->task->data['load_balancer_network_ids'])) {
            $data = $this->task->data;

            $loadBalancer->loadBalancerNetworks()->each(function ($loadBalancerNetwork) use (&$data, $loadBalancer) {
                Log::info(get_class($this) . ': Deleting load balancer network ' . $loadBalancerNetwork->id, ['id' => $loadBalancer->id]);
                $loadBalancerNetwork->syncDelete();
                $data['load_balancer_network_ids'][] = $loadBalancerNetwork->id;
            });

            $this->task->setAttribute('data', $data)->saveQuietly();
        }

        if (isset($this->task->data['load_balancer_network_ids'])) {
            $this->awaitSyncableResources($this->task->data['load_balancer_network_ids']);
        }
    }
}
