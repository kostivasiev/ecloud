<?php

namespace Tests\unit\Jobs\Vip;

use App\Jobs\Vip\AssignToLoadBalancerCluster;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\Entities\Vip;
use UKFast\SDK\SelfResponse;

class AssignToLoadBalancerClusterTest extends TestCase
{
    use LoadBalancerMock, VipMock;

    public function testCreateVipSuccess()
    {
        $clusterIp = $this->vip()->assignClusterIp();
        $this->floatingIp()->resource()->associate($clusterIp)->save();

        $this->loadBalancer()->setAttribute('config_id', 321)->saveQuietly();

        $vipEntity = app()->make(Vip::class);
        $vipEntity->internalCidr = '10.0.0.4/32';
        $vipEntity->externalCidr = '1.1.1.1/32';

        $mockAdminLoadbalancersClient = \Mockery::mock(AdminClient::class);
        $mockAdminLoadbalancersClient->allows('vips->createEntity')
            ->andReturnUsing(function () {
                $responseMock = \Mockery::mock(SelfResponse::class)->makePartial();
                $responseMock->allows('getId')->andReturns(111);
                return $responseMock;
            });
        $mockAdminLoadbalancersClient->allows('setResellerId')->andReturns($mockAdminLoadbalancersClient);

        app()->bind(AdminClient::class, function () use ($mockAdminLoadbalancersClient) {
            return $mockAdminLoadbalancersClient;
        });

        $task = $this->createSyncUpdateTask($this->vip());

        dispatch(new AssignToLoadBalancerCluster($task));

        Event::assertNotDispatched(JobFailed::class);
    }
}
