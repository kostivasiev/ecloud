<?php

namespace Tests\unit\Jobs\Instance\Undeploy;

use App\Jobs\Instance\Undeploy\DetachSharedVolumes;
use App\Jobs\Kingpin\Instance\DetachVolume;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\Mocks\Resources\VolumeMock;
use Tests\TestCase;

class DetachSharedVolumesTest extends TestCase
{
    use VolumeGroupMock, VolumeMock;

    private Task $task;


    /** @test */
    public function volumeDetachesSuccessfully()
    {
        $this->volume()
            ->setAttribute('is_shared', true)
            ->setAttribute('port', 0)
            ->saveQuietly();

        $this->instance()->volumes()->attach($this->volume());
        $this->instance()->saveQuietly();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->instance());
        });

        Bus::fake([DetachVolume::class]);

        $detachSharedVolumes = \Mockery::mock(DetachSharedVolumes::class, [$this->task])->makePartial();
        $detachSharedVolumes->allows('awaitTaskWithRelease')
            ->with(\Mockery::capture($subTask))
            ->andReturnTrue();

        $detachSharedVolumes->handle();

        $this->assertEquals($this->volume()->id, $subTask->data['volume_id']);
    }
}
