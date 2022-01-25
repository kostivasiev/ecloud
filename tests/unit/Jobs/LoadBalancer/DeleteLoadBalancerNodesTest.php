<?php

namespace Tests\unit\Jobs\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancer\DeleteLoadBalancerNodes;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Mocks\Resources\LoadBalancerMock;

class DeleteLoadBalancerNodesTest extends TestCase
{
    use LoadBalancerMock;

    public function testNodesAreDeleted()
    {
        $this->loadBalancerNode()
            ->setAttribute('node_id', 123456)
            ->saveQuietly();
        $this->loadBalancerNode()->instance()->associate($this->instance());

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

        dispatch(new DeleteLoadBalancerNodes($task));

        Event::assertNotDispatched(JobFailed::class);

        $task->refresh();
        $this->assertNotNull($task->data['load_balancer_node_ids']);
    }
}
