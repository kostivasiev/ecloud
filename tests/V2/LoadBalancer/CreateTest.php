<?php

namespace Tests\V2\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Models\V2\Network;
use App\Models\V2\Router;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\AdminClusterClient;
use UKFast\Api\Auth\Consumer;
use UKFast\SDK\SelfResponse;

class CreateTest extends TestCase
{
    use LoadBalancerMock;

    private $lbConfigId = 123456;

    public function setUp(): void
    {
        parent::setUp();
        $managementRouter = Model::withoutEvents(function () {
            return factory(Router::class)->create([
                'id' => 'rtr-mgmt',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'router_throughput_id' => $this->routerThroughput()->id,
                'is_management' => true,
            ]);
        });

        Model::withoutEvents(function () use ($managementRouter) {
            return factory(Network::class)->create([
                'id' => 'net-mgmt',
                'name' => 'Manchester Network',
                'subnet' => '10.0.0.0/24',
                'router_id' => $managementRouter->id
            ]);
        });

        app()->bind(AdminClient::class, function () {
            $mock = \Mockery::mock(AdminClient::class)->makePartial();
            $mock->allows('setResellerId')->andReturnSelf();
            $mock->allows('clusters')->andReturnUsing(function () {
                $clusterMock = \Mockery::mock(AdminClusterClient::class)->makePartial();
                $clusterMock->allows('createEntity')
                    ->withAnyArgs()
                    ->andReturnUsing(function () {
                        $mockSelfResponse = \Mockery::mock(SelfResponse::class)->makePartial();
                        $mockSelfResponse->allows('getId')->andReturns($this->lbConfigId);
                        return $mockSelfResponse;
                    });
                return $clusterMock;
            });
            return $mock;
        });

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testInvalidVpcIdIsFailed()
    {
        $this->post(
            '/v2/load-balancers',
            [
                'name' => 'My Load Balancer',
                'load_balancer_spec_id' => $this->loadBalancerSpecification()->id,
                'vpc_id' => Str::uuid(),
                'availability_zone_id' => $this->availabilityZone()->id
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The specified vpc id was not found',
            'status' => 422,
            'source' => 'vpc_id'
        ])->assertResponseStatus(422);
    }

    public function testInvalidAvailabilityZoneIsFailed()
    {
        $this->post(
            '/v2/load-balancers',
            [
                'name' => 'My Load Balancer',
                'load_balancer_spec_id' => $this->loadBalancerSpecification()->id,
                'vpc_id' => Str::uuid(),
                'availability_zone_id' => Str::uuid()
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The specified vpc id was not found',
            'status' => 422,
            'source' => 'vpc_id'
        ])->assertResponseStatus(422);
    }

    public function testNotOwnedVpcIdIsFailed()
    {
        $this->be(new Consumer(2, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->post(
            '/v2/load-balancers',
            [
                'name' => 'My Load Balancer',
                'load_balancer_spec_id' => $this->loadBalancerSpecification()->id,
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => Str::uuid(),
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The specified vpc id was not found',
            'status' => 422,
            'source' => 'vpc_id'
        ])->assertResponseStatus(422);
    }

    public function testFailedVpcCausesFail()
    {
        $this->createSyncUpdateTask($this->vpc())
            ->setAttribute('failure_reason', 'Unit Test Failure')
            ->setAttribute('completed', true)
            ->saveQuietly();

        $this->post(
            '/v2/load-balancers',
            [
                'name' => 'My Load Balancer',
                'load_balancer_spec_id' => $this->loadBalancerSpecification()->id,
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id
            ]
        )->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The specified vpc id resource is currently in a failed state and cannot be used',
            ]
        )->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        Event::fake(Created::class);

        $this->post(
            '/v2/load-balancers',
            [
                'name' => 'My Load Balancer',
                'vpc_id' => $this->vpc()->id,
                'load_balancer_spec_id' => $this->loadBalancerSpecification()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'network_id' => $this->network()->id,
            ]
        )->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }
}
