<?php

namespace Tests\unit\Middleware\LoadBalancer;

use App\Http\Middleware\Loadbalancer\IsMaxForForCustomer;
use App\Models\V2\LoadBalancer;
use App\Models\V2\LoadBalancerSpecification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsMaxForCustomerTest extends TestCase
{
    use LoadBalancerMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(false));
    }

    public function testLimitReachedFails()
    {
        Config::set('load-balancer.customer_max_per_az', 2);
        $this->loadBalancer();

        $request = Request::create(
            'POST',
            '/v2/load-balancers',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'load_balancer_spec_id' => $this->loadBalancerSpecification()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vpc_id' => $this->vpc()->id,
            ]));

        $middleware = new IsMaxForForCustomer();

        $response = $middleware->handle($request, function () {});

        $this->assertEquals($response->getStatusCode(), 403);
    }

    public function testLimitNotReachedPasses()
    {
        Config::set('load-balancer.customer_max_per_az', 2);

        $request = Request::create(
            'POST',
            '/v2/load-balancers',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'load_balancer_spec_id' => $this->loadBalancerSpecification()->id,
                'availability_zone_id' => $this->availabilityZone(),
                'vpc_id' => $this->vpc()->id,
            ]));

        $middleware = new IsMaxForForCustomer();

        $response = $middleware->handle($request, function () {});

        $this->assertNull($response);
    }
}
