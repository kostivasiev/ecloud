<?php

namespace Tests\unit\Jobs\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancer\DeleteVips;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Mocks\Resources\LoadBalancerMock;

class DeleteVipsTest extends TestCase
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
        $this->loadBalancerInstance();

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

        dispatch(new DeleteVips($task));

        Event::assertNotDispatched(JobFailed::class);

        $task->refresh();

        $this->assertNotNull($task->data['vip_ids']);
    }
}
