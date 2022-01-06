<?php

namespace Tests\unit\Jobs\Network;

use App\Events\V2\Task\Created;
use App\Jobs\Network\DeleteNetworkPolicy;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteNetworkPolicyTest extends TestCase
{
    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->network());
            $this->task->save();
        });
    }

    public function testSucceeds()
    {
        Event::fake([JobFailed::class, Created::class]);

        $this->networkPolicy();

        $this->assertEquals(1, $this->network()->networkPolicy()->count());

        dispatch(new DeleteNetworkPolicy($this->task));

        Event::assertDispatched(Created::class);

        Event::assertNotDispatched(JobFailed::class);
    }
}
