<?php

namespace Tests\unit\Jobs\Vip;

use App\Jobs\Vip\UnassignFromLoadBalancerCluster;
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

        $notFoundException = \Mockery::mock(\UKFast\SDK\Exception\NotFoundException::class)->makePartial();
        $notFoundException->allows('getStatusCode')->andReturns(404);
        $mockAdminLoadbalancersClient->allows('vips->destroy')->andThrow($notFoundException);

        app()->bind(AdminClient::class, function () use ($mockAdminLoadbalancersClient) {
            return $mockAdminLoadbalancersClient;
        });

        $this->vip()->setAttribute('config_id', 111)->save();

        $task = $this->createSyncDeleteTask($this->vip());

        dispatch(new UnassignFromLoadBalancerCluster($task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testUnexpectedErrorFails()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $mockAdminLoadbalancersClient = \Mockery::mock(AdminClient::class);
        $mockAdminLoadbalancersClient->allows('setResellerId')->andReturns($mockAdminLoadbalancersClient);

        $apiException = \Mockery::mock(\UKFast\SDK\Exception\ApiException::class)->makePartial();
        $apiException->allows('getStatusCode')->andReturns(500);
        $mockAdminLoadbalancersClient->allows('vips->destroy')->andThrow($apiException);

        app()->bind(AdminClient::class, function () use ($mockAdminLoadbalancersClient) {
            return $mockAdminLoadbalancersClient;
        });

        $this->vip()->setAttribute('config_id', 111)->save();

        $task = $this->createSyncDeleteTask($this->vip());

        $this->expectException(\UKFast\SDK\Exception\ApiException::class);

        dispatch(new UnassignFromLoadBalancerCluster($task));

        Event::assertDispatched(JobFailed::class);
    }
}
