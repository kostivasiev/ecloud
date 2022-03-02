<?php

namespace Tests\unit\Jobs\LoadBalancerNode;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancerNode\UnregisterNode;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\AdminNodeClient;
use UKFast\SDK\Exception\ApiException;

class UnregisterNodeTest extends TestCase
{
    use LoadBalancerMock;
    public int $lbNodeId = 123456;

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
                $nodeMock->allows('deleteById')
                    ->withAnyArgs()
                    ->andReturnTrue();
                return $nodeMock;
            });
            return $mock;
        });
    }

    public function testSuccess()
    {
        $this->loadBalancerNode()->setAttribute('node_id', $this->lbNodeId)->saveQuietly();
        $task = $this->createSyncDeleteTask($this->loadBalancerNode());

        Event::fake([JobFailed::class, Created::class]);

        dispatch(new UnregisterNode($task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testDeleteNodeThatDoesNotExist()
    {
        $exceptionMessage = 'Loadbalancer node not found, skipping';
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->loadBalancerNode()->setAttribute('node_id', 123456)->saveQuietly();
        $task = $this->createSyncDeleteTask($this->loadBalancerNode());

        app()->bind(AdminClient::class, function () {
            $clientMock = \Mockery::mock(AdminClient::class)->makePartial();
            $clientMock->allows('setResellerId')->andReturnSelf();
            $clientMock->allows('nodes')
                ->andReturnUsing(function () {
                    $mock = \Mockery::mock(AdminNodeClient::class)->makePartial();
                    $mock->allows('deleteById')
                        ->withAnyArgs()
                        ->andThrow(new ApiException(new Response(404, [], json_encode(['error'=>'error']))));
                    return $mock;
                });
            return $clientMock;
        });

        $job = \Mockery::mock(UnregisterNode::class, [$task])->makePartial();
        $job->allows('info')
            ->andThrows(new \Exception($exceptionMessage));
        $job->handle();
    }
}
