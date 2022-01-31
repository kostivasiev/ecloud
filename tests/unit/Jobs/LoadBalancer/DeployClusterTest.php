<?php

namespace Tests\unit\Jobs\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancer\DeployCluster;
use App\Models\V2\Task;
use App\Support\Sync;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\AdminClusterClient;
use UKFast\Admin\Loadbalancers\Entities\Cluster;
use UKFast\SDK\Exception\ApiException;

class DeployClusterTest extends TestCase
{
    use LoadBalancerMock, VipMock;

    private int $lbConfigId = 123456;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSuccessfulUpdate()
    {
        $task = $this->getAdminClientMock()
            ->setLoadbalancerConfigId($this->lbConfigId)
            ->getTask();

        Event::fake([JobFailed::class, Created::class]);
        dispatch(new DeployCluster($task));
        Event::assertNotDispatched(JobFailed::class);
    }

    public function testValidButSkipDeploy()
    {
        $task = $this->getAdminClientMock(false, false)
            ->setLoadbalancerConfigId($this->lbConfigId)
            ->getTask();

        Event::fake([JobFailed::class, Created::class]);
        dispatch(new DeployCluster($task));
        Event::assertNotDispatched(JobFailed::class);
    }

    public function testInvalidClusterId()
    {
        $task = $this->getAdminClientMock(true, false)
            ->setLoadbalancerConfigId($this->lbConfigId)
            ->getTask();

        Event::fake([JobFailed::class, Created::class]);
        dispatch(new DeployCluster($task));
        Event::assertNotDispatched(JobFailed::class);
    }

    public function testFailDeployment()
    {
        $this->expectException(ApiException::class);
        $task = $this->getAdminClientMock(true, true)
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
    private function getAdminClientMock(bool $fails = false, bool $deployed = true): DeployClusterTest
    {
        app()->bind(AdminClient::class, function () use ($fails, $deployed) {
            $mock = \Mockery::mock(AdminClient::class)->makePartial();
            $mock->allows('setResellerId')
                ->andReturnSelf();
            $mock->allows('clusters')->andReturnUsing(function () use ($fails, $deployed) {
                $clusterMock = \Mockery::mock(AdminClusterClient::class)->makePartial();
                if ($fails && !$deployed) {
                    $apiException = \Mockery::mock(ApiException::class)->makePartial();
                    $apiException->allows('getStatusCode')->andReturns(404);
                    $clusterMock->allows('getById')
                        ->with($this->lbConfigId)
                        ->andThrows($apiException);
                } else {
                    $clusterMock->allows('getById')
                        ->with($this->lbConfigId)
                        ->andReturnUsing(function () use ($deployed) {
                            return new Cluster([
                                'id' => $this->lbConfigId,
                                'deployed_at' => ($deployed) ? Carbon::now() : null,
                            ]);
                        });
                }
                if ($fails) {
                    $apiException = \Mockery::mock(ApiException::class)->makePartial();
                    $apiException->allows('getStatusCode')->andReturns(422);
                    $clusterMock->allows('deploy')
                        ->with($this->lbConfigId)
                        ->andThrows($apiException);
                } else {
                    $clusterMock->allows('deploy')
                        ->with($this->lbConfigId)
                        ->andReturnUsing(function () {
                            return new Response(204);
                        });
                }
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
