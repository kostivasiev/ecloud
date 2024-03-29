<?php

namespace Tests\Unit\Jobs\Volume;

use App\Jobs\Kingpin\Instance\DetachVolume;
use App\Jobs\Volume\UnAssignVolumeGroup;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\Mocks\Resources\VolumeMock;
use Tests\TestCase;

class UnAssignVolumeGroupTest extends TestCase
{
    use VolumeGroupMock, VolumeMock;

    private Task $task;

    /** @test */
    public function skipIfInstanceVolumeGroupIsEmpty()
    {
        $this->volume()->setAttribute('volume_group_id', $this->volumeGroup()->id)->saveQuietly();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->volume());
        });

        $this->assertEmpty((new UnAssignVolumeGroup($this->task))->handle());
    }

    /** @test */
    public function skipIfVolumeGroupIdIsNotEmpty()
    {
        $this->instanceModel()->setAttribute('volume_group_id', $this->volumeGroup()->id)->saveQuietly();
        $this->volume()->setAttribute('volume_group_id', $this->volumeGroup()->id)->saveQuietly();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->volume());
        });

        $this->assertEmpty((new UnAssignVolumeGroup($this->task))->handle());
    }

    /** @test */
    public function volumeDetachesSuccessfully()
    {
        // attach volume to instance and set volume_group_id
        $this->instanceModel()->setAttribute('volume_group_id', $this->volumeGroup()->id);
        $this->instanceModel()->volumes()->attach($this->volume());
        $this->instanceModel()->saveQuietly();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->volume());
        });

        Bus::fake([DetachVolume::class]);

        $unassignVolumeGroup = \Mockery::mock(UnAssignVolumeGroup::class, [$this->task])->makePartial();
        $unassignVolumeGroup->allows('awaitTaskWithRelease')
            ->with(\Mockery::capture($subTask))
            ->andReturnTrue();

        $unassignVolumeGroup->handle();
        $this->assertEquals($this->volume()->id, $subTask->data['volume_id']);
        $this->assertNull($this->volume()->port);
    }
}
