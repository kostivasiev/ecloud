<?php

namespace Tests\unit\Jobs\LoadBalancerNode;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancerNode\UpdateNode;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\AdminNodeClient;
use UKFast\SDK\SelfResponse;

class UpdateNodeTest extends TestCase
{
    use LoadBalancerMock;

    private $lbNodeId = 123456;
    protected string $ipAddress;
    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();
        $this->ipAddress = '192.168.1.10';
        $this->router()->setAttribute('is_management', true)->saveQuietly();
        $this->nic()->setAttribute('ip_address', $this->ipAddress)->saveQuietly();
        $this->loadBalancerNode()->setAttribute('node_id', $this->lbNodeId)->saveQuietly();
        $this->loadBalancerInstance();
        $this->task = Task::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->loadBalancerNode());
            $task->save();
            return $task;
        });
    }

    public function testSuccess()
    {
        app()->bind(AdminClient::class, function () {
            $mock = \Mockery::mock(AdminClient::class)->makePartial();
            $mock->allows('setResellerId')->andReturnSelf();
            $mock->allows('nodes')->andReturnUsing(function () {
                $nodeMock = \Mockery::mock(AdminNodeClient::class)->makePartial();
                $nodeMock->allows('updateEntity')
                    ->withAnyArgs()
                    ->andReturnUsing(function () {
                        $mockSelfResponse = \Mockery::mock(SelfResponse::class)->makePartial();
                        $mockSelfResponse->allows('getId')->andReturns($this->lbNodeId);
                        return $mockSelfResponse;
                    });
                return $nodeMock;
            });
            return $mock;
        });

        Event::fake([JobFailed::class, Created::class]);

        dispatch(new UpdateNode($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testGetManagementNic()
    {
        $job = new UpdateNode($this->task);
        $this->assertEquals($this->ipAddress, $job->getManagementNic()->ip_address);
    }
}
