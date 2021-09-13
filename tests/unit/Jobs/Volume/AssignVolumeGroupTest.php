<?php

namespace Tests\unit\Jobs\Volume;

use App\Jobs\Volume\AssignVolumeGroup;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\Mocks\Resources\VolumeMock;
use Tests\TestCase;

class AssignVolumeGroupTest extends TestCase
{
    use VolumeGroupMock, VolumeMock;

    private Task $task;

    /** @test */
    public function skipIfVolumeAlreadyMounted()
    {
        // Assign volume group to instance
        $this->instance()->setAttribute('volume_group_id', $this->volumeGroup()->id)
            ->saveQuietly();

        // attach volume to volume group
        $this->volume()
            ->setAttribute('volume_group_id', $this->volumeGroup()->id)
            ->saveQuietly();

        // assign volume to instance
        $this->instance()->volumes()->attach($this->volume());
        $this->instance()->saveQuietly();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->volume());
        });

        $this->assertEmpty((new AssignVolumeGroup($this->task))->handle());
    }
}
