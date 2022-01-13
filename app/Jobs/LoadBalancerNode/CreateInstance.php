<?php

namespace App\Jobs\LoadBalancerNode;

use App\Jobs\TaskJob;
use App\Models\V2\Instance;
use Illuminate\Support\Facades\Log;

class CreateInstance extends TaskJob
{
    public function handle()
    {
        $loadBalancerNode = $this->task->resource;
        $loadBalancer = $loadBalancerNode->loadBalancer;
        $nodeIndex = $this->task->data['node_index'];

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

        $instance = app()->make(Instance::class);
        $instance->fill([
            'name' => 'Load Balancer ' . $nodeIndex,
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
                    "node_id" => $nodeIndex,
                ],
            ],
            'is_hidden' => true
        ]);
        $instance->save();
        $loadBalancerNode->setAttribute('instance_id', $instance->id)->saveQuietly();
    }
}
