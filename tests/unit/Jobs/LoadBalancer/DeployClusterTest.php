<?php

namespace Tests\unit\Jobs\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancer\DeployCluster;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\AdminClusterClient;

class DeployClusterTest extends TestCase
{
    use LoadBalancerMock, VipMock;

    private int $lbConfigId = 123456;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSuccessful()
    {
        $task = $this->getAdminClientMock()
            ->setLoadbalancerConfigId($this->lbConfigId)
            ->getTask();

        Event::fake([JobFailed::class, Created::class]);
        dispatch(new DeployCluster($task));
        Event::assertNotDispatched(JobFailed::class);
    }

    public function testFails()
    {
        $task = $this->getAdminClientMock(true)
            ->setLoadbalancerConfigId($this->lbConfigId)
            ->getTask();

        Event::fake([JobFailed::class, Created::class]);
        dispatch(new DeployCluster($task));
        Event::assertDispatched(JobFailed::class);
    }

    /**
     * Gets AdminClient mock
     * @param bool $fails
     * @return DeployClusterTest
     */
    private function getAdminClientMock(bool $fails = false): DeployClusterTest
    {
        app()->bind(AdminClient::class, function () use ($fails) {
            $mock = \Mockery::mock(AdminClient::class)->makePartial();
            $mock->allows('setResellerId')
                ->andReturnSelf();
            $mock->allows('clusters')->andReturnUsing(function () use ($fails) {
                $clusterMock = \Mockery::mock(AdminClusterClient::class)->makePartial();
                $clusterMock->allows('deploy')
                    ->with($this->lbConfigId)
                    ->andReturnUsing(function () use ($fails) {
                        if ($fails) {
                            return new Response(422, [], json_encode(['errors' => ['Error is thrown here']]));
                        }
                        return new Response(204);
                    });
                return $clusterMock;
            });
            return $mock;
        });
        return $this;
    }

    /**
     * Sets the loadbalancer config_id value
     * @param int|null $value
     * @return DeployClusterTest
     */
    private function setLoadbalancerConfigId(?int $value): DeployClusterTest
    {
        $this->loadBalancer()
            ->setAttribute('config_id', $value)
            ->saveQuietly();
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
            $task->resource()->associate($this->vip());
            $task->save();
            return $task;
        });
    }
}
