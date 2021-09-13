<?php

namespace Tests\unit\Jobs\Instance;

use App\Jobs\Instance\VolumeGroupDetach;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
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

        // kingpin mocks
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/instance/i-test')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['volumes' => []]));
            });

        (new VolumeGroupDetach($this->task))->handle();

        $this->assertEquals(0, $this->instance()->volumes()->count());
    }
}
