<?php

namespace Tests\unit\Jobs\Network;

use App\Events\V2\Task\Created;
use App\Jobs\Network\DeleteManagementNetworks;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteManagementNetworksTest extends TestCase
{
    protected Task $task;

    public function testDeleteManagementNetwork()
    {
        Event::fake(Created::class);
        $this->router()->setAttribute('is_hidden', true)->saveQuietly();
        $this->network()->router()->associate($this->router());

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->vpc());
        });

        $job = new DeleteManagementNetworks($this->task);
        $job->handle();
        $this->assertTrue(in_array($this->network()->id, $this->task->data['management_network_ids']));
        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        });
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

        $job = new DeleteManagementNetworks($this->task);
        $job->handle();

        $this->assertEquals(0, count($this->task->data['management_network_ids']));
    }
}
