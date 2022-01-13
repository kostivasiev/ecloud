<?php

namespace Tests\unit\Jobs\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancer\DeleteNodes;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class DeleteNodesTest extends TestCase
{
    use LoadBalancerMock;

    public function testSuccess()
    {
        $this->loadBalancerNode();
        $task = Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $task->resource()->associate($this->loadBalancer());
            $task->save();
            return $task;
        });

        Event::fake([JobFailed::class, Created::class]);

        dispatch(new DeleteNodes($task));

        Event::assertNotDispatched(JobFailed::class);
    }
}
