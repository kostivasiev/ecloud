<?php

namespace Tests\Unit\Jobs\Volume;

use App\Jobs\Kingpin\Instance\AttachVolume;
use App\Jobs\Kingpin\Volume\IopsChange;
use App\Jobs\Volume\AssignVolumeGroup;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
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
        $this->instanceModel()->setAttribute('volume_group_id', $this->volumeGroup()->id)
            ->saveQuietly();

        // Assign volume group to volume
        $this->volume()
            ->setAttribute('volume_group_id', $this->volumeGroup()->id)
            ->saveQuietly();

        // Attach volume to instance
        $this->instanceModel()->volumes()->attach($this->volume());
        $this->instanceModel()->saveQuietly();

        Bus::fake([AttachVolume::class, IopsChange::class]);

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->volume());
        });

        $this->assertEmpty((new AssignVolumeGroup($this->task))->handle());
    }

    /** @test */
    public function volumeAttachesSuccessfully()
    {
        // Assign volume group to instance
        $this->instanceModel()->setAttribute('volume_group_id', $this->volumeGroup()->id)
            ->saveQuietly();

        // Assign volume group to volume
        $this->volume()
            ->setAttribute('volume_group_id', $this->volumeGroup()->id)
            ->saveQuietly();

        Bus::fake([AttachVolume::class, IopsChange::class]);

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->volume());
        });

        $assignVolumeGroup = \Mockery::mock(AssignVolumeGroup::class, [$this->task])
            ->makePartial();
        $assignVolumeGroup->allows('awaitTaskWithRelease')
            ->with(\Mockery::capture($subTask))
            ->andReturnTrue();

        $assignVolumeGroup->handle();
        $this->assertEquals($this->volume()->id, $subTask->data['volume_id']);
    }
}
