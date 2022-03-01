<?php

namespace Tests\unit\Jobs\LoadBalancerNetwork;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancerNetwork\DeleteNics;
use App\Models\V2\IpAddress;
use App\Support\Sync;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;

class DeleteNicsTest extends TestCase
{
    use LoadBalancerMock, VipMock;

    public function testDeletesNetworkNic()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        // Create a VIP and assign a cluster IP to it.
        $this->vip()->assignClusterIp();

        // Create a load balancer node and instance
        $this->loadBalancerNode();

        // Create a NIC on the instance/mode
        $this->nic()->setAttribute('instance_id', $this->loadBalancerInstance()->id)->saveQuietly();

        $this->assertCount(1, $this->loadBalancerInstance()->nics);

        // Assign a DHCP address to the NIC
        $this->nic()->assignIpAddress();

        $this->kingpinServiceMock()->expects('delete')
            ->withArgs([
                '/api/v2/vpc/' . $this->vpc()->id .
                '/instance/' . $this->loadBalancerInstance()->id .
                '/nic/AA:BB:CC:DD:EE:FF'
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        $task = $this->createSyncUpdateTask($this->loadBalancerNetwork());

        dispatch(new DeleteNics($task));

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        });

        $task->refresh();

        $this->assertNotNull($task->data['task.' . Sync::TASK_NAME_DELETE. '.ids']);

        // Mark the delete sync task as completed
        $syncTask = Event::dispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        })->first()[0];

        $syncTask->model->setAttribute('completed', true)->saveQuietly();

        dispatch(new DeleteNics($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testClusterIpAssignedFails()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        // Create a VIP and assign a cluster IP to it.
        $this->vip()->assignClusterIp();

        // Create a load balancer node and instance
        $this->loadBalancerNode();

        // Create a NIC on the instance/node
        $this->nic()->setAttribute('instance_id', $this->loadBalancerInstance()->id)->saveQuietly();

        // Assign a DHCP address to the NIC
        $this->nic()->assignIpAddress();

        // Assign a cluster IP to the NIC
        $this->nic()->ipAddresses()->save($this->vip()->ipAddress);

        $task = $this->createSyncDeleteTask($this->loadBalancerNetwork());

        dispatch(new DeleteNics($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(JobFailed::class, function ($event) {
            return $event->exception->getMessage() == 'Failed to delete NIC ' . $this->nic()->id . ', ' . IpAddress::TYPE_CLUSTER . ' IP detected';
        });

        Event::assertNotDispatched(Created::class);
    }

    public function testReleasedWhenSyncing()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        // Create a VIP and assign a cluster IP to it.
        $this->vip()->assignClusterIp();

        // Create a load balancer node and instance
        $this->loadBalancerNode();

        // Create a NIC on the instance/mode
        $this->nic()->setAttribute('instance_id', $this->loadBalancerInstance()->id)->saveQuietly();

        $this->assertCount(1, $this->loadBalancerInstance()->nics);

        // Assign a DHCP address to the NIC
        $this->nic()->assignIpAddress();

        $this->kingpinServiceMock()->expects('delete')
            ->withArgs([
                '/api/v2/vpc/' . $this->vpc()->id .
                '/instance/' . $this->loadBalancerInstance()->id .
                '/nic/AA:BB:CC:DD:EE:FF'
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        $task = $this->createSyncUpdateTask($this->loadBalancerNetwork());

        dispatch(new DeleteNics($task));

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        });

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testNicNotFoundSucceeds()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        // Create a VIP and assign a cluster IP to it.
        $this->vip()->assignClusterIp();

        // Create a load balancer node and instance
        $this->loadBalancerNode();

        // Create a NIC on the instance/mode
        $this->nic()->setAttribute('instance_id', $this->loadBalancerInstance()->id)->saveQuietly();

        $this->assertCount(1, $this->loadBalancerInstance()->nics);

        // Assign a DHCP address to the NIC
        $this->nic()->assignIpAddress();

        $this->kingpinServiceMock()->expects('delete')
            ->withArgs([
                '/api/v2/vpc/' . $this->vpc()->id .
                '/instance/' . $this->loadBalancerInstance()->id .
                '/nic/AA:BB:CC:DD:EE:FF'
            ])
            ->andThrow(new RequestException('Not Found', new Request('DELETE', 'test'), new Response(404)));

        $task = $this->createSyncUpdateTask($this->loadBalancerNetwork());

        dispatch(new DeleteNics($task));

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        });

        Event::assertDispatched(JobProcessed::class);

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testKingpinErrorFails()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        // Create a VIP and assign a cluster IP to it.
        $this->vip()->assignClusterIp();

        // Create a load balancer node and instance
        $this->loadBalancerNode();

        // Create a NIC on the instance/mode
        $this->nic()->setAttribute('instance_id', $this->loadBalancerInstance()->id)->saveQuietly();

        $this->assertCount(1, $this->loadBalancerInstance()->nics);

        // Assign a DHCP address to the NIC
        $this->nic()->assignIpAddress();

        $this->kingpinServiceMock()
            ->expects('delete')
            ->andThrow(new RequestException('Server Error', new Request('DELETE', 'test'), new Response(500)));

        $task = $this->createSyncUpdateTask($this->loadBalancerNetwork());

        $this->expectException(RequestException::class);

        dispatch(new DeleteNics($task));

        Event::assertDispatched(JobProcessed::class);

        Event::assertNotDispatched(\App\Events\V2\Task\Created::class);

        Event::assertDispatched(JobFailed::class);
    }
}
