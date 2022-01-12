<?php

namespace Tests\Mocks\Resources;

use App\Models\V2\Instance;
use App\Models\V2\LoadBalancer;
use App\Models\V2\LoadBalancerNetwork;
use App\Models\V2\LoadBalancerSpecification;
use Illuminate\Database\Eloquent\Model;

trait LoadBalancerMock
{
    protected $loadBalancerSpecification;
    protected $loadBalancer;
    protected $loadBalancerInstance;
    protected $loadBalancerNetwork;

    public function loadBalancerSpecification($id = 'lbs-test'): LoadBalancerSpecification
    {
        if (!isset($this->loadBalancerSpecification)) {
            Model::withoutEvents(function () use ($id) {
                $this->loadBalancerSpecification = factory(LoadBalancerSpecification::class)->create([
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
                $this->loadBalancer = factory(LoadBalancer::class)->create([
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
            Model::withoutEvents(function () use ($id) {
                $this->loadBalancerInstance = factory(Instance::class)->create([
                    'id' => $id,
                    'vpc_id' => $this->vpc()->id,
                    'name' => 'Load Balancer ' . uniqid(),
                    'image_id' => $this->image()->id,
                    'vcpu_cores' => 1,
                    'ram_capacity' => 1024,
                    'platform' => 'Linux',
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
        return $this->loadBalancerInstance;
    }

    public function loadBalancerNetwork($id = 'lbn-test'): LoadBalancerNetwork
    {
        if (!isset($this->loadBalancerNetwork)) {
            $this->loadBalancerNetwork = LoadBalancerNetwork::factory()->make(['id' => $id]);
            $this->loadBalancerNetwork->loadBalancer()->associate($this->loadBalancer());
            $this->loadBalancerNetwork->network()->associate($this->network());
            $this->loadBalancerNetwork->save();
        }

        return $this->loadBalancerNetwork;
    }
}