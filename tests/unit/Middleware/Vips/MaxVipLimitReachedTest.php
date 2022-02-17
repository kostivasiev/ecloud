<?php

namespace Tests\Unit\Middleware\Vips;

use App\Http\Middleware\Vips\MaxVipLimitReached;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class MaxVipLimitReachedTest extends TestCase
{
    use LoadBalancerMock;

    protected MaxVipLimitReached $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new MaxVipLimitReached();
    }

    public function testLimitReached()
    {
        Config::set('load-balancer.limits.vips-max', 0);
        $response = $this->middleware->handle($this->getRequest(), function () {
            //
        });

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testLimitNotReached()
    {
        $response = $this->middleware->handle($this->getRequest(), function () {
            //
        });
        $this->assertNull($response);
    }

    protected function getRequest(): Request
    {
        $payload = json_encode(['load_balancer_network_id' => $this->loadBalancerNetwork()->id]);
        $headers = ['CONTENT_TYPE' => 'application/json'];
        return Request::create('POST', '/v2/vips', [], [], [], $headers, $payload);
    }
}