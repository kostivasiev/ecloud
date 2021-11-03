<?php

namespace Tests\Mocks\Resources;

use App\Models\V2\LoadBalancer;
use App\Models\V2\LoadBalancerSpecification;
use Illuminate\Database\Eloquent\Model;

trait LoadBalancerMock
{
   protected $loadBalancerSpecification;

    protected $loadBalancer;

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
}