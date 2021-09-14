<?php

namespace Tests\unit\Jobs\Volume;

use App\Jobs\Kingpin\Instance\DetachVolume;
use App\Jobs\Volume\UnAssignVolumeGroup;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\Mocks\Resources\VolumeMock;
use Tests\TestCase;

class UnAssignVolumeGroupTest extends TestCase
{
    use VolumeGroupMock, VolumeMock;

    private Task $task;

    /** @test */
    public function skipIfVolumeGroupIdIsNotEmpty()
    {
        Log::partialMock()
            ->expects('info')
            ->withSomeOfArgs('Volume is not associated with a volume group, skipping')
            ->once();

        $this->instance()->setAttribute('volume_group_id', $this->volumeGroup()->id)->saveQuietly();
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
        $this->instance()->setAttribute('volume_group_id', $this->volumeGroup()->id);
        $this->instance()->volumes()->attach($this->volume());
        $this->instance()->saveQuietly();

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
    }
}
