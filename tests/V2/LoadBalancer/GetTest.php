<?php

namespace Tests\V2\LoadBalancer;

use App\Models\V2\LoadBalancer;
use App\Models\V2\LoadBalancerSpecification;
use Faker\Factory as Faker;
use Tests\TestCase;

class GetTest extends TestCase
{
    protected $loadBalancer;
    protected $loadBalancerSpec;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->loadBalancerSpec = factory(LoadBalancerSpecification::class)->create();
        $this->loadBalancer = factory(LoadBalancer::class)->create([
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id,
            'load_balancer_spec_id' => $this->loadBalancerSpec->id
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/load-balancers',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->loadBalancer->id,
                'name' => $this->loadBalancer->name,
                'vpc_id' => $this->loadBalancer->vpc_id,
                'load_balancer_spec_id' => $this->loadBalancer->load_balancer_spec_id,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->loadBalancer->instances()->save($this->instance());

        $this->get(
            '/v2/load-balancers/' . $this->loadBalancer->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->loadBalancer->id,
                'name' => $this->loadBalancer->name,
                'vpc_id' => $this->loadBalancer->vpc_id,
                'load_balancer_spec_id' => $this->loadBalancer->load_balancer_spec_id,
                'nodes' => 1,
            ])
            ->assertResponseStatus(200);
    }

}
