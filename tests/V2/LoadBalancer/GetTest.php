<?php

namespace Tests\V2\LoadBalancer;

use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Models\V2\Nic;
use Faker\Factory as Faker;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    use LoadBalancerMock;
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
            ->assertJsonFragment([
                'id' => $this->loadBalancer()->id,
                'name' => $this->loadBalancer()->name,
                'vpc_id' => $this->loadBalancer()->vpc_id,
                'load_balancer_spec_id' => $this->loadBalancer()->load_balancer_spec_id,
                'network_id' => $this->network()->id,
            ])
            ->assertStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/load-balancers/' . $this->loadBalancer()->id)
            ->assertJsonFragment([
                'id' => $this->loadBalancer()->id,
                'name' => $this->loadBalancer()->name,
                'vpc_id' => $this->loadBalancer()->vpc_id,
                'load_balancer_spec_id' => $this->loadBalancer()->load_balancer_spec_id,
                'nodes' => 1,
                'network_id' => $this->network()->id,
            ])
            ->assertStatus(200);
    }

    public function testGetLoadBalancerNetworksCollection()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->loadBalancerNetwork();
        $this->get('/v2/load-balancers/' . $this->loadBalancer()->id . '/networks')
            ->assertJsonFragment([
                'id' => $this->loadBalancerNetwork()->id,
                'name' => $this->loadBalancerNetwork()->name,
                'load_balancer_id' => $this->loadBalancer()->id,
                'network_id' => $this->network()->id,
            ])
            ->assertStatus(200);
    }

    public function testGetLoadBalancerAvailableTargetsCollection()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->loadBalancerNetwork();

        $router = $this->loadBalancer()->networks->first()->router;
        $network = $router->networks()->create([
            'name' => 'test-network'
        ]);

        Nic::create([
            'mac_address' => '00:00:00:00:00:00',
            'instance_id' => $this->instanceModel()->id,
            'network_id' => $network->id,
        ]);
        
        $this->get('/v2/load-balancers/' . $this->loadBalancer()->id . '/available-targets')
            ->assertJsonFragment([
                'id' => $this->instanceModel()->id,
            ])
            ->assertJsonMissing([
                'id' => $this->loadBalancer()->id
            ])
            ->assertStatus(200);
    }
}
