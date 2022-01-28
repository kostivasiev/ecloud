<?php

namespace Tests\unit\Jobs\LoadBalancerNode;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancerNode\CreateTargetGroup;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\AdminTargetGroupClient;
use UKFast\SDK\SelfResponse;

class CreateTargetGroupTest extends TestCase
{
    use LoadBalancerMock;

    private int $lbConfigId = 123456;
    private int $lbNodeId = 123456;

    public function setUp(): void
    {
        parent::setUp();
        $this->ipAddress = '192.168.1.10';
        $this->router()->setAttribute('is_management', true)->saveQuietly();
        $this->nic()->setAttribute('ip_address', $this->ipAddress)->saveQuietly();
        $this->loadBalancerNode()->setAttribute('node_id', $this->lbNodeId)->saveQuietly();
        $this->loadBalancerInstance();
    }

    public function testSuccessful()
    {
        $task = $this->getAdminClientMock()
            ->setLoadbalancerConfigId($this->lbConfigId)
            ->getTask();

        Event::fake([JobFailed::class, Created::class]);
        dispatch(new CreateTargetGroup($task));
        Event::assertNotDispatched(JobFailed::class);
    }

    public function testFailure()
    {
        $task = $this->getAdminClientMock(true)
            ->setLoadbalancerConfigId($this->lbConfigId)
            ->getTask();

        Event::fake([JobFailed::class, Created::class]);
        dispatch(new CreateTargetGroup($task));
        Event::assertDispatched(JobFailed::class);
    }

    /**
     * Sets the loadbalancer config_id value
     * @param int|null $value
     * @return CreateTargetGroupTest
     */
    private function setLoadbalancerConfigId(?int $value): CreateTargetGroupTest
    {
        $this->loadBalancer()
            ->setAttribute('config_id', $value)
            ->saveQuietly();
        return $this;
    }

    /**
     * Gets AdminClient mock
     * @param bool $fails
     * @return CreateTargetGroupTest
     */
    private function getAdminClientMock(bool $fails = false): CreateTargetGroupTest
    {
        app()->bind(AdminClient::class, function () use ($fails) {
            $mock = \Mockery::mock(AdminClient::class)->makePartial();
            $mock->allows('setResellerId')
                ->andReturnSelf();
            $mock->allows('targetGroups')->andReturnUsing(function () use ($fails) {
                $clusterMock = \Mockery::mock(AdminTargetGroupClient::class)->makePartial();
                if ($fails) {
                    $clusterMock->allows('createEntity')
                        ->withAnyArgs()
                        ->andThrow(new ClientException(
                            'Invalid',
                            new Request('POST', '/', []),
                            new Response(422, [], json_encode(['errors' => ['Error is shown here']]))
                        ));
                } else {
                    $clusterMock->allows('createEntity')
                        ->withAnyArgs()
                        ->andReturnUsing(function () {
                            $mockSelfResponse = \Mockery::mock(SelfResponse::class)->makePartial();
                            $mockSelfResponse->allows('getId')->andReturns(1);
                            return $mockSelfResponse;
                        });
                }
                return $clusterMock;
            });
            return $mock;
        });
        return $this;
    }

    /**
     * Gets Task for delete job
     * @return Task
     */
    private function getTask(): Task
    {
        return Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->loadBalancerNode());
            $task->save();
            return $task;
        });
    }
}
