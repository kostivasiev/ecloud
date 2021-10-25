<?php

namespace Tests\unit\Middleware\LoadBalancer;

use App\Http\Middleware\Loadbalancer\IsMaxForForCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsMaxForCustomerTest extends TestCase
{
    protected OrchestratorConfig $orchestratorConfig;

    public function setUp(): void
    {
        parent::setUp();
        $this->orchestratorConfig = factory(OrchestratorConfig::class)->create();
    }

    public function testIsNotValidOrchestratorConfigFails()
    {
        Config::set('load-balancer.customer_max_per_az', 1);

        // Create a loadbalancer instace

        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $request = Request::create(
            'POST',
            '/v2/load-balancers',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            'INVALID JSON');

        $middleware = new IsMaxForForCustomer();

        $response = $middleware->handle($request, function () {});

        $this->assertEquals($response->getStatusCode(), 422);
    }

    public function testIsValidOrchestratorConfigPasses()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $request = Request::create(
            'POST',
            '/v2/orchestrator-configs/' . $this->orchestratorConfig->id . '/data',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['foo' => 'bar']));

        $middleware = new IsMaxForForCustomer();

        $response = $middleware->handle($request, function () {});

        $this->assertNull($response);
    }
}
