<?php

namespace Tests\unit\Jobs\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancer\CreateNodes;
use App\Models\V2\LoadBalancerNode;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class CreateNodesTest extends TestCase
{
    use LoadBalancerMock;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSuccess()
    {
        // Create the management network
        $this->router()->setAttribute('is_management', true)->save();
        $this->network();

        $task = Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->loadBalancer());
            $task->save();
            return $task;
        });


        Event::fake([JobFailed::class, Created::class]);

        dispatch(new CreateNodes($task));

        Event::assertDispatched(Created::class, function ($event) {
            return (
                $event->model->resource instanceof LoadBalancerNode
                && $event->model->name == Sync::TASK_NAME_UPDATE
            );
        });

        Event::assertNotDispatched(JobFailed::class);

        $task->refresh();
        $loadBalancerNodes = $task->resource->loadBalancerNodes;

        // Check that the number of nodes created is correct according to the LB Spec
        $this->assertEquals($this->loadBalancerSpecification()->node_count, $loadBalancerNodes->count());
    }
}
