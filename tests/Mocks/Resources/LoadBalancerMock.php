<?php

namespace Tests\Mocks\Resources;

use App\Models\V2\Instance;
use App\Models\V2\LoadBalancer;
use App\Models\V2\LoadBalancerNode;
use App\Models\V2\LoadBalancerNetwork;
use App\Models\V2\LoadBalancerSpecification;
use Illuminate\Database\Eloquent\Model;

trait LoadBalancerMock
{
    protected LoadBalancerSpecification $loadBalancerSpecification;
    protected LoadBalancer $loadBalancer;
    protected Instance $loadBalancerInstance;
    protected LoadBalancerNode $loadBalancerNode;
    protected array $loadBalancerHANodes;
    protected LoadBalancerNetwork $loadBalancerNetwork;

    public function loadBalancerSpecification($id = 'lbs-test'): LoadBalancerSpecification
    {
        if (!isset($this->loadBalancerSpecification)) {
            Model::withoutEvents(function () use ($id) {
                $this->loadBalancerSpecification = LoadBalancerSpecification::factory()->create([
                    'id' => $id,
                    'name' => 'medium',
                ]);
            });
        }
        return $this->loadBalancerSpecification;
    }

    public function loadBalancer($id = 'lb-test'): LoadBalancer
    {
        if (!isset($this->loadBalancer)) {
            Model::withoutEvents(function () use ($id) {
                $this->loadBalancer = LoadBalancer::factory()->create([
                    'id' => $id,
                    'name' => $id,
                    'availability_zone_id' => $this->availabilityZone()->id,
                    'vpc_id' => $this->vpc()->id,
                    'load_balancer_spec_id' => $this->loadBalancerSpecification()->id,
                ]);
            });
        }
        return $this->loadBalancer;
    }

    public function loadBalancerInstance($id = 'i-lbtest'): Instance
    {
        if (!isset($this->loadBalancerInstance)) {
            $this->loadBalancerInstance = $this->newLoadBalancerInstance($id);
        }
        return $this->loadBalancerInstance;
    }

    public function newLoadBalancerInstance($id = 'i-lbtest'): Instance
    {
        return Model::withoutEvents(function () use ($id) {
            return factory(Instance::class)->create([
                'id' => $id,
                'vpc_id' => $this->vpc()->id,
                'name' => 'Load Balancer ' . uniqid(),
                'image_id' => $this->image()->id,
                'vcpu_cores' => 1,
                'ram_capacity' => 1024,
                'availability_zone_id' => $this->availabilityZone()->id,
                'deploy_data' => [
                    'network_id' => $this->network()->id,
                    'volume_capacity' => 20,
                    'volume_iops' => 300,
                    'requires_floating_ip' => false,
                ],
                'load_balancer_id' => $this->loadBalancer()->id,
                'is_hidden' => true,
            ]);
        });
    }

    public function loadBalancerNode($id = 'ln-test'): LoadBalancerNode
    {
        if (!isset($this->loadBalancerNode)) {
            $this->loadBalancerNode = factory(LoadBalancerNode::class)->create([
                'id' => $id,
                'instance_id' => $this->loadBalancerInstance()->id,
                'load_balancer_id' => $this->loadBalancer()->id,
                'node_id' => null,
            ]);
        }
        return $this->loadBalancerNode;
    }

    public function loadBalancerHANodes()
    {
        if (!isset($this->loadBalancerHANodes)) {
            Model::withoutEvents(function () {
                $this->loadBalancerHANodes = [];
                $this->loadBalancerHANodes[] = factory(LoadBalancerNode::class)->create([
                    'id' => 'lbn-test1',
                    'instance_id' => $this->newLoadBalancerInstance('i-test1'),
                    'load_balancer_id' => $this->loadBalancer()->id,
                    'node_id' => null,
                ]);
                $this->loadBalancerHANodes[] = factory(LoadBalancerNode::class)->create([
                    'id' => 'lbn-test2',
                    'instance_id' => $this->newLoadBalancerInstance('i-test2'),
                    'load_balancer_id' => $this->loadBalancer()->id,
                    'node_id' => null,
                ]);
            });
        }
        return $this->loadBalancerHANodes;
    }

    public function loadBalancerNetwork($id = 'lbn-test'): LoadBalancerNetwork
    {
        if (!isset($this->loadBalancerNetwork)) {
            $this->loadBalancerNetwork = LoadBalancerNetwork::factory()->make([
                'id' => $id,
                'name' => $id
            ]);
            $this->loadBalancerNetwork->loadBalancer()->associate($this->loadBalancer());
            $this->loadBalancerNetwork->network()->associate($this->network());
            $this->loadBalancerNetwork->save();
        }

        return $this->loadBalancerNetwork;
    }
}