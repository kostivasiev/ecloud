<?php

namespace Tests\unit\Jobs\LoadBalancerNode;

use App\Jobs\LoadBalancerNode\RegisterNode;
use App\Models\V2\Task;
use App\Support\Sync;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\AdminNodeClient;
use UKFast\SDK\SelfResponse;

class RegisterNodeTest extends TestCase
{
    use LoadBalancerMock;

    private $lbNodeId = 123456;

    public function setUp(): void
    {
        parent::setUp();
        $this->router()->setAttribute('is_management', true)->save();
        $this->network();
        app()->bind(AdminClient::class, function () {
            $mock = \Mockery::mock(AdminClient::class)->makePartial();
            $mock->allows('setResellerId')->andReturnSelf();
            $mock->allows('nodes')->andReturnUsing(function () {
                $nodeMock = \Mockery::mock(AdminNodeClient::class)->makePartial();
                $nodeMock->allows('createEntity')
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
    }

    public function testRegisterLoadbalancer()
    {
        $task = Task::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->loadBalancerNode());
            $task->save();
            return $task;
        });
        $job = new RegisterNode($task);
        $job->handle();

        $this->assertEquals($this->lbNodeId, $this->loadBalancerNode->node_id);
    }
}
