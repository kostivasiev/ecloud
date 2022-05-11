<?php

namespace Tests\Unit\Jobs\Instance;

use App\Jobs\Instance\VolumeGroupAttach;
use App\Jobs\Kingpin\Instance\AttachVolume;
use App\Jobs\Kingpin\Volume\IopsChange;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\Mocks\Resources\VolumeMock;
use Tests\TestCase;

class VolumeGroupAttachTest extends TestCase
{
    use VolumeGroupMock, VolumeMock;

    private Task $task;

    /** @test */
    public function skipIfVolumeAlreadyMounted()
    {
        Log::partialMock()
            ->expects('info')
            ->withSomeOfArgs('Volume is already mounted on Instance, skipping')
            ->once();

        // add volume to volume group
        $this->volume()->volume_group_id = $this->volumeGroup()->id;
        $this->volume()->is_shared = true;
        $this->volume()->port = 0;
        $this->volume()->saveQuietly();

        // add volume group to instance and attach the volume
        $this->instanceModel()->volume_group_id = $this->volumeGroup()->id;
        $this->instanceModel()->volumes()->attach($this->volume());
        $this->instanceModel()->saveQuietly();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->instanceModel());
        });

        $this->assertEmpty((new VolumeGroupAttach($this->task))->handle());
    }

    /** @test */
    public function volumeAttachesSuccessfully()
    {
        // add volume to volume group
        $this->volume()->volume_group_id = $this->volumeGroup()->id;
        $this->volume()->is_shared = true;
        $this->volume()->port = 0;
        $this->volume()->saveQuietly();

        // add volume group to instance
        $this->instanceModel()->volume_group_id = $this->volumeGroup()->id;
        $this->instanceModel()->saveQuietly();

        Bus::fake([AttachVolume::class, IopsChange::class]);

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->instanceModel());
        });

        $volumeGroupAttach = \Mockery::mock(VolumeGroupAttach::class, [$this->task])
            ->makePartial();
        $volumeGroupAttach->allows('awaitTaskWithRelease')
            ->with(\Mockery::capture($subTask))
            ->andReturnTrue();

        $volumeGroupAttach->handle();

        $this->assertEquals($this->volume()->id, $subTask->data['volume_id']);
    }
}
