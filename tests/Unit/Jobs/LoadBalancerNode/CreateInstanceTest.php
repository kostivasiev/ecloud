<?php

namespace Tests\Unit\Jobs\LoadBalancerNode;

use App\Jobs\LoadBalancerNode\CreateInstance;
use App\Models\V2\Task;
use App\Support\Sync;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class CreateInstanceTest extends TestCase
{
    use LoadBalancerMock;

    public function testSuccessful()
    {
        $task = Task::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
                'data' => [
                    'node_index' => 1,
                ],
            ]);
            $task->resource()->associate($this->loadBalancerNode());
            $task->save();
            return $task;
        });
        $job = new CreateInstance($task);
        $job->handle();

        $this->assertNotNull($this->loadBalancerNode()->instance_id);
    }
}
