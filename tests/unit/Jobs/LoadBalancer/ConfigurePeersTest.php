<?php

namespace Tests\unit\Jobs\LoadBalancer;

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
        app()->bind(AdminClient::class, function () {
            $mock = \Mockery::mock(AdminClient::class)->makePartial();
            $mock->allows('setResellerId')->andReturnSelf();
            $mock->allows('clusters')->andReturnUsing(function () {
                $clusterMock = \Mockery::mock(AdminClusterClient::class)->makePartial();
                $clusterMock->allows('configurePeers')
                    ->with($this->loadBalancer()->config_id)
                    ->andReturnTrue();
                return $clusterMock;
            });
            return $mock;
        });
    }

    public function testSuccessful()
    {
        $task = $this->createSyncUpdateTask($this->loadBalancer());
        Event::fake([JobFailed::class, Created::class]);

        dispatch(new ConfigurePeers($task));
        Event::assertNotDispatched(JobFailed::class);
    }
}
