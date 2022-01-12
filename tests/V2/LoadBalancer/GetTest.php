<?php

namespace Tests\V2\LoadBalancer;

use App\Models\V2\LoadBalancer;
use App\Models\V2\LoadBalancerSpecification;
use Faker\Factory as Faker;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

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
                'id' => $this->loadBalancer()->id,
                'name' => $this->loadBalancer()->name,
                'vpc_id' => $this->loadBalancer()->vpc_id,
                'load_balancer_spec_id' => $this->loadBalancer()->load_balancer_spec_id,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get(
            '/v2/load-balancers/' . $this->loadBalancer()->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->loadBalancer()->id,
                'name' => $this->loadBalancer()->name,
                'vpc_id' => $this->loadBalancer()->vpc_id,
                'load_balancer_spec_id' => $this->loadBalancer()->load_balancer_spec_id,
                'nodes' => 1,
            ])
            ->assertResponseStatus(200);
    }

}
