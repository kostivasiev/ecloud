<?php

namespace Tests\V2\LoadBalancer;

use Faker\Factory as Faker;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    use LoadBalancerMock;
    protected $loadBalancer;
    protected $loadBalancerSpec;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->loadBalancerSpecification();
        $this->loadBalancer();
        $this->loadBalancerNode();
        $this->loadBalancerNetwork();

        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(false));
    }

    public function testGetCollection()
    {
        $this->get('/v2/load-balancers')
            ->seeJson([
                'id' => $this->loadBalancer()->id,
                'name' => $this->loadBalancer()->name,
                'vpc_id' => $this->loadBalancer()->vpc_id,
                'load_balancer_spec_id' => $this->loadBalancer()->load_balancer_spec_id,
                'network_id' => $this->network()->id,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/load-balancers/' . $this->loadBalancer()->id)
            ->seeJson([
                'id' => $this->loadBalancer()->id,
                'name' => $this->loadBalancer()->name,
                'vpc_id' => $this->loadBalancer()->vpc_id,
                'load_balancer_spec_id' => $this->loadBalancer()->load_balancer_spec_id,
                'nodes' => 1,
                'network_id' => $this->network()->id,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetLoadBalancerNetworksCollection()
    {
        $this->loadBalancerNetwork();
        $this->get('/v2/load-balancers/' . $this->loadBalancer()->id . '/networks')
            ->seeJson([
                'id' => $this->loadBalancerNetwork()->id,
                'name' => $this->loadBalancerNetwork()->name,
                'load_balancer_id' => $this->loadBalancer()->id,
                'network_id' => $this->network()->id,
            ])
            ->assertResponseStatus(200);
    }
}
