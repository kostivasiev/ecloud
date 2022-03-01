<?php

namespace Tests\unit\Jobs\Network;

use App\Events\V2\Task\Created;
use App\Jobs\Network\AwaitNetworkPolicyRemoval;
use App\Jobs\Network\DeleteNetworkPolicy;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AwaitNetworkPolicyRemovalTest extends TestCase
{
    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();
        $this->networkPolicy();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->network());
            $this->task->save();
        });
    }

    public function testHasAssociatedNetworkPolicyRetries()
    {
        Event::fake([JobFailed::class, Created::class]);

        $this->assertEquals(1, $this->network()->networkPolicy()->count());

        dispatch(new AwaitNetworkPolicyRemoval($this->task));

        Log::shouldReceive('warning')->withSomeOfArgs('Network still has an associated network policy, retrying in 5 seconds', ['id' => $this->network()->id]);

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testPolicyDeletedSucceeds()
    {
        $this->networkPolicy()->delete();

        $this->assertEquals(0, $this->network()->networkPolicy()->count());

        dispatch(new AwaitNetworkPolicyRemoval($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testFailedPolicyFails()
    {
        Event::fake([JobFailed::class]);
        $task = new Task([
            'id' => 'task-1',
            'completed' => false,
            'failure_reason' => 'test',
            'name' => Sync::TASK_NAME_DELETE,
        ]);
        $task->resource()->associate($this->networkPolicy());
        $task->save();

        dispatch(new AwaitNetworkPolicyRemoval($this->task));

        Event::assertDispatched(JobFailed::class);
    }
}
