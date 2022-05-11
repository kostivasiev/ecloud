<?php

namespace Tests\Unit\Jobs\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancer\ConfigurePeers;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\AdminClusterClient;

class ConfigurePeersTest extends TestCase
{
    use LoadBalancerMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->loadBalancer()->setAttribute('config_id', 123456)->saveQuietly();
    }

    public function testSuccessful()
    {
        $task = $this->createSyncUpdateTask($this->loadBalancer());
        $task->setAttribute('data', ['loadbalancer_node_ids' => 'foo'])->saveQuietly();

        Event::fake([JobFailed::class, Created::class]);

        $adminLoadBalancersClient = \Mockery::mock(AdminClient::class)->makePartial();
        $adminLoadBalancersClient->expects('setResellerId')->andReturnSelf();
        $adminLoadBalancersClient->expects('clusters')->andReturnUsing(function () {
            $adminClusterClient = \Mockery::mock(AdminClusterClient::class)->makePartial();
            $adminClusterClient->expects('configurePeers')
                ->with($this->loadBalancer()->config_id)
                ->andReturnTrue();
            return $adminClusterClient;
        });

        app()->bind(AdminClient::class, function () use ($adminLoadBalancersClient) {
            return $adminLoadBalancersClient;
        });

        dispatch(new ConfigurePeers($task));
        Event::assertNotDispatched(JobFailed::class);
    }

    public function testNoNodesCreatedSkips()
    {
        $task = $this->createSyncUpdateTask($this->loadBalancer());
        Event::fake([JobFailed::class, Created::class]);

        $adminLoadBalancersClient = \Mockery::mock(AdminClient::class)->makePartial();
        $adminLoadBalancersClient->shouldNotReceive('setResellerId');

        app()->bind(AdminClient::class, function () use ($adminLoadBalancersClient) {
            return $adminLoadBalancersClient;
        });

        dispatch(new ConfigurePeers($task));

        Event::assertNotDispatched(JobFailed::class);
    }
}
