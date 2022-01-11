<?php

namespace App\Jobs\LoadBalancerNode;

use App\Jobs\Job;
use App\Models\V2\LoadBalancerNode;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\Entities\Node;

class RegisterNode extends Job
{
    use Batchable, LoggableModelJob;

    private LoadBalancerNode $model;
    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        $loadBalancerNode = $this->model;
        if ($loadBalancerNode->loadBalancer->id === null) {
            Log::info('RegisterNode for ' . $loadBalancerNode->loadBalancer->id . ', no loadbalancer so nothing to do');
            return;
        }

        $client = app()->make(AdminClient::class)
            ->setResellerId($loadBalancerNode->loadBalancer->getResellerId());
        $response = $client->nodes()->createEntity(
            $loadBalancerNode->loadBalancer->config_id,
            new Node([
                'vendor_type' => 'eCloud',
                'vendor_id' => $loadBalancerNode->instance_id,
            ])
        );
        Log::info('Registering instance as loadbalancer node', [
            'id' => $loadBalancerNode->instance->id,
            'cluster_id' => $loadBalancerNode->loadBalancer->config_id,
            'node_id' => $response->getId(),
        ]);
        $loadBalancerNode->setAttribute('node_id', $response->getId())->saveQuietly();
    }
}
