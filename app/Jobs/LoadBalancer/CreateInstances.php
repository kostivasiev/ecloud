<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\Job;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Models\V2\LoadBalancer;
use App\Models\V2\LoadBalancerNode;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateInstances extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;
    
    private LoadBalancer $model;
    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        $loadBalancer = $this->model;
        $managementRouter = $loadBalancer->availabilityZone->routers()
            ->where('is_management', true)
            ->whereHas('vpc', function ($query) use ($loadBalancer) {
                $query->where('id', $loadBalancer->vpc->id);
            })->first();

        if (!$managementRouter) {
            Log::error('Failed to load management router', [
                'vpc_id' => $loadBalancer->vpc->id,
                'availability_zone_id' => $loadBalancer->availabilityZone->id,
                'load_balancer_id' => $loadBalancer->id]);

            $this->fail(new \Exception('Failed to load management router'));
            return;
        }

        $managementNetwork = $managementRouter->networks->first();

        if (!$managementNetwork) {
            Log::error('Failed to load management network', [
                'vpc_id' => $loadBalancer->vpc->id,
                'availability_zone_id' => $loadBalancer->availabilityZone->id,
                'load_balancer_id' => $loadBalancer->id]);

            $this->fail(new \Exception('Failed to load management network'));
            return;
        }

        for ($i = 0; $i < $loadBalancer->loadBalancerSpec->node_count; $i++) {
            $instance = app()->make(Instance::class);
            $instance->fill([
                'name' => 'Load Balancer ' . ($i+1),
                'vpc_id' => $loadBalancer->vpc->id,
                'availability_zone_id' => $loadBalancer->availabilityZone->id,
                'image_id' => $loadBalancer->loadBalancerSpec->image_id,
                'vcpu_cores' => $loadBalancer->loadBalancerSpec->cpu,
                'ram_capacity' => (1024 * $loadBalancer->loadBalancerSpec->ram),
                'deploy_data' => [
                    'network_id' => $managementNetwork->id,
                    'volume_capacity' => $loadBalancer->loadBalancerSpec->hdd,
                    'volume_iops' => $loadBalancer->loadBalancerSpec->iops,
                    'requires_floating_ip' => false,
                    'floating_ip_id' => null,
                    'user_script' => null,
                    'ssh_key_pair_ids' => null,
                    'software_ids' => null,
                    'image_data' => [
                        "node_id" => ($i+1),
                    ],
                ],
                'is_hidden' => true
            ]);
            $instance->save();

            $node = app()->make(LoadBalancerNode::class);
            $node->fill([
                'load_balancer_id' => $loadBalancer->id,
                'instance_id' => $instance->id,
                'node_id' => null,
            ]);
            $node->syncSave();
        }
    }
}
