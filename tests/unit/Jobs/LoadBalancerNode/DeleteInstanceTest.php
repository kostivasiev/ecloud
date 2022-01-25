<?php

namespace Tests\unit\Jobs\LoadBalancerNode;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancerNode\DeleteInstance;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;
use function dispatch;

class DeleteInstanceTest extends TestCase
{
    use LoadBalancerMock;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSuccess()
    {
        $task = Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_DELETE,
                'data' => [
                    'instance_id' => $this->instance()->id,
                ],
            ]);
            $task->resource()->associate($this->loadBalancerNode());
            $task->save();
            return $task;
        });

        Event::fake([JobFailed::class, Created::class]);

        dispatch(new DeleteInstance($task));

        Event::assertNotDispatched(JobFailed::class);

        $task->refresh();
        $this->assertNotNull($task->data['instance_id']);
    }
}
