<?php

namespace Tests\unit\Jobs\Network;

use App\Jobs\Network\DeleteManagementNetwork;
use App\Listeners\V2\TaskCreated;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteManagementNetworkTest extends TestCase
{
    protected Task $task;

    public function testDeleteManagementNetwork()
    {
        $this->router()->setAttribute('is_hidden', true)->saveQuietly();
        $this->network()->router()->associate($this->router());

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->vpc());
        });
        Event::fake(TaskCreated::class);
        Bus::fake();

        $job = new DeleteManagementNetwork($this->task);
        $job->handle();
        $this->assertTrue(in_array($this->network()->id, $this->task->data['management_network_ids']));
    }

    public function testSkipDeletingManagementNetwork()
    {
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->vpc());
        });
        Event::fake(TaskCreated::class);
        Bus::fake();

        $job = new DeleteManagementNetwork($this->task);
        $job->handle();

        $this->assertEquals(0, count($this->task->data['management_network_ids']));
    }
}
