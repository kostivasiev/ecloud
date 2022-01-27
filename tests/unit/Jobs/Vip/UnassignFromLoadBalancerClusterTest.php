<?php

namespace Tests\unit\Jobs\Vip;

use App\Jobs\Vip\UnassignFromLoadBalancerCluster;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;
use UKFast\Admin\Loadbalancers\AdminClient;

class UnassignFromLoadBalancerClusterTest extends TestCase
{
    use LoadBalancerMock, VipMock;

    public function testSuccess()
    {
        Event::fake(JobFailed::class);

        $mockAdminLoadbalancersClient = \Mockery::mock(AdminClient::class);
        $mockAdminLoadbalancersClient->allows('setResellerId')->andReturns($mockAdminLoadbalancersClient);
        $mockAdminLoadbalancersClient->allows('vips->destroy')->andReturnTrue();

        app()->bind(AdminClient::class, function () use ($mockAdminLoadbalancersClient) {
            return $mockAdminLoadbalancersClient;
        });

        $this->vip()->setAttribute('config_id', 111)->save();

        $task = $this->createSyncDeleteTask($this->vip());

        dispatch(new UnassignFromLoadBalancerCluster($task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testNotFoundPasses()
    {
        Event::fake(JobFailed::class);

        $mockAdminLoadbalancersClient = \Mockery::mock(AdminClient::class);
        $mockAdminLoadbalancersClient->allows('setResellerId')->andReturns($mockAdminLoadbalancersClient);
        $mockAdminLoadbalancersClient->allows('vips->destroy')->andThrow(
            new RequestException('Not Found', new Request('GET', 'test'), new Response(404))
        );

        app()->bind(AdminClient::class, function () use ($mockAdminLoadbalancersClient) {
            return $mockAdminLoadbalancersClient;
        });

        $this->vip()->setAttribute('config_id', 111)->save();

        $task = $this->createSyncDeleteTask($this->vip());

        dispatch(new UnassignFromLoadBalancerCluster($task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testNoConfigIdFails()
    {
        Event::fake(JobFailed::class);

        $task = $this->createSyncDeleteTask($this->vip());

        dispatch(new UnassignFromLoadBalancerCluster($task));

        Event::assertDispatched(JobFailed::class, function ($event) {
            return $event->exception->getMessage() == 'Failed to unassign VIP from load balancer cluster, no config_id was set.';
        });
    }

    public function testUnexpectedErrorFails()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $mockAdminLoadbalancersClient = \Mockery::mock(AdminClient::class);
        $mockAdminLoadbalancersClient->allows('setResellerId')->andReturns($mockAdminLoadbalancersClient);
        $mockAdminLoadbalancersClient->allows('vips->destroy')->andThrow(
            new RequestException('Server Error', new Request('DELETE', 'test'), new Response(500))
        );

        app()->bind(AdminClient::class, function () use ($mockAdminLoadbalancersClient) {
            return $mockAdminLoadbalancersClient;
        });

        $this->vip()->setAttribute('config_id', 111)->save();

        $task = $this->createSyncDeleteTask($this->vip());

        $this->expectExceptionCode(500);

        dispatch(new UnassignFromLoadBalancerCluster($task));

        Event::assertDispatched(JobFailed::class);
    }
}
