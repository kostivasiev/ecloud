<?php

namespace Tests\Unit\Middleware\Vips;

use App\Http\Middleware\Vips\MaxVipLimitReached;
use App\Models\V2\LoadBalancerNetwork;
use App\Models\V2\Network;
use App\Models\V2\Vip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class MaxVipLimitReachedTest extends TestCase
{
    use LoadBalancerMock, VipMock;

    protected MaxVipLimitReached $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new MaxVipLimitReached();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testLimitReached()
    {
        Config::set('load-balancer.limits.vips-max', 0);
        $response = $this->middleware->handle($this->getRequest(), $this->closure());

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testLimitNotReached()
    {
        $response = $this->middleware->handle($this->getRequest(), $this->closure());
        $this->assertNull($response);
    }

    public function testLimitReachedMultipleNetworks()
    {
        Config::set('load-balancer.limits.vips-max', 3); // Max 3

        // Should pass
        $response = $this->middleware->handle($this->getRequest(), $this->closure());
        $this->assertNull($response);

        // Should pass
        $response = $this->middleware->handle($this->getRequest(), $this->closure());
        $this->assertNull($response);

        // Should pass
        $response = $this->middleware->handle($this->getRequest(), $this->closure());
        $this->assertNull($response);

        // Should fail
        $response = $this->middleware->handle($this->getRequest(), $this->closure());
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Forbidden', $response->getData()->title);
        $this->assertEquals('A maximum of 3 vips can be assigned per load balancer.', $response->getData()->detail);
    }

    protected function getRequest(): Request
    {
        $this->createNewLoadBalancerNetwork();
        $payload = json_encode(['load_balancer_id' => $this->loadBalancer()->id]);
        $headers = ['CONTENT_TYPE' => 'application/json'];
        return Request::create('POST', '/v2/vips', [], [], [], $headers, $payload);
    }

    protected function closure()
    {
        return function () {
            // does nothing
        };
    }

    protected function createNewLoadBalancerNetwork(): LoadBalancerNetwork
    {
        $network = Model::withoutEvents(function () {
            $id = $this->getId('net');
            return factory(Network::class)->create([
                'id' => $id,
                'name' => $id,
                'subnet' => long2ip(rand(0, 4294967295)) . '/24',
                'router_id' => $this->router()->id
            ]);
        });
        return Model::withoutEvents(function () use ($network) {
            $id = $this->getId('lbn');
            $loadBalancerNetwork = LoadBalancerNetwork::factory()->create([
                'id' => $id,
                'name' => $id,
                'load_balancer_id' => $this->loadBalancer()->id,
                'network_id' => $network->id,
            ]);
            Vip::factory()->create([
                'id' => $id,
                'name' => $id,
                'load_balancer_network_id' => $loadBalancerNetwork->id,
            ]);
            return $loadBalancerNetwork;
        });
    }

    protected function getId(string $prefix = ''): string
    {
        return uniqid($prefix . '-') . '-dev';
    }
}