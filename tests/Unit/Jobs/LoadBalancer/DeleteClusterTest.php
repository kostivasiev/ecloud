<?php

namespace Tests\Unit\Jobs\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancer\DeleteCluster;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\AdminClusterClient;

class DeleteClusterTest extends TestCase
{
    use LoadBalancerMock;

    private int $lbConfigId = 123456;

    /**
     * @test Delete succeeds
     */
    public function testDeleteSucceeds()
    {
        $task = $this->getAdminClientMock(false)
            ->setLoadbalancerConfigId($this->lbConfigId)
            ->getTask();
        Event::fake([JobFailed::class, Created::class]);
        dispatch(new DeleteCluster($task));
        Event::assertNotDispatched(JobFailed::class);
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
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $task->resource()->associate($this->loadBalancer());
            $task->save();
            return $task;
        });
    }

    /**
     * Sets the loadbalancer config_id value
     * @param int|null $value
     * @return DeleteClusterTest
     */
    private function setLoadbalancerConfigId(?int $value): DeleteClusterTest
    {
        $this->loadBalancer()
            ->setAttribute('config_id', $value)
            ->saveQuietly();
        return $this;
    }

    /**
     * Gets AdminClient mock
     * @param bool $fails
     * @return DeleteClusterTest
     */
    private function getAdminClientMock(bool $fails = false): DeleteClusterTest
    {
        app()->bind(AdminClient::class, function () use ($fails) {
            $mock = \Mockery::mock(AdminClient::class)->makePartial();
            $mock->allows('setResellerId')
                ->andReturnSelf();
            $mock->allows('clusters')->andReturnUsing(function () use ($fails) {
                $clusterMock = \Mockery::mock(AdminClusterClient::class)->makePartial();
                $clusterMock->allows('deleteById')
                    ->with($this->lbConfigId)
                    ->andReturn(!$fails);
                return $clusterMock;
            });
            return $mock;
        });
        return $this;
    }

    /**
     * @test Delete fails, but the job should ignore that
     */
    public function testDeleteFails()
    {
        $task = $this->getAdminClientMock(true)
            ->setLoadbalancerConfigId($this->lbConfigId)
            ->getTask();
        Event::fake([JobFailed::class, Created::class]);
        dispatch(new DeleteCluster($task));
        Event::assertNotDispatched(JobFailed::class);
    }

    /**
     * @test Skip doing anything if there is no config_id set
     */
    public function testDeleteSkipped()
    {
        $task = $this->setLoadbalancerConfigId(null)
            ->getTask();
        Event::fake([JobFailed::class, Created::class]);
        dispatch(new DeleteCluster($task));
        Event::assertNotDispatched(JobFailed::class);
    }
}
