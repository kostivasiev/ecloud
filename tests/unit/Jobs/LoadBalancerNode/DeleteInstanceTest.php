<?php

namespace Tests\unit\Jobs\LoadBalancerNode;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancerNode\DeleteInstance;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;
use function dispatch;

class DeleteInstanceTest extends TestCase
{
    use LoadBalancerMock;

    public function testSuccess()
    {
        $taskIdKey = 'task.' . Sync::TASK_NAME_DELETE . '.id';
        $task = $this->createSyncDeleteTask($this->loadBalancerNode());

        Event::fake([JobFailed::class, Created::class]);

        dispatch(new DeleteInstance($task));

        Event::assertNotDispatched(JobFailed::class);

        $task->refresh();
        $this->assertNotNull($task->data[$taskIdKey]);
    }

    public function testWhenTheInstanceDoesNotExist()
    {
        $exceptionMessage = 'Instance not found, nothing to delete';
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->loadBalancerNode()->setAttribute('instance_id', 'i-dddddd')->saveQuietly();
        $task = $this->createSyncDeleteTask($this->loadBalancerNode());

        $job = \Mockery::mock(DeleteInstance::class, [$task])
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $job->allows('info')
            ->andThrows(new \Exception($exceptionMessage));

        $job->handle();
    }
}
