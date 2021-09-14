<?php

namespace Tests\unit\Jobs\Instance;

use App\Jobs\Instance\VolumeGroupDetach;
use App\Jobs\Kingpin\Instance\DetachVolume;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\Mocks\Resources\VolumeMock;
use Tests\TestCase;

class VolumeGroupDetachTest extends TestCase
{
    use VolumeGroupMock, VolumeMock;

    private Task $task;

    /** @test */
    public function skipIfVolumeGroupIdIsNotEmpty()
    {
        Log::partialMock()
            ->expects('info')
            ->withSomeOfArgs('Instance is not associated with a volume group, skipping')
            ->once();

        // add volume group to instance and attach the volume
        $this->instance()->volume_group_id = $this->volumeGroup()->id;
        $this->instance()->volumes()->attach($this->volume());
        $this->instance()->saveQuietly();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->instance());
        });

        $this->assertEmpty((new VolumeGroupDetach($this->task))->handle());
    }

    /** @test */
    public function volumeDetachesSuccessfully()
    {
        $this->volume()->is_shared = true;
        $this->volume()->port = 0;
        $this->volume()->saveQuietly();
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

        $volumeGroupDetach = \Mockery::mock(VolumeGroupDetach::class, [$this->task])->makePartial();
        $volumeGroupDetach->allows('awaitTaskWithRelease')
            ->with(\Mockery::capture($subTask))
            ->andReturnTrue();

        $volumeGroupDetach->handle();

        $this->assertEquals($this->volume()->id, $subTask->data['volume_id']);
    }
}
