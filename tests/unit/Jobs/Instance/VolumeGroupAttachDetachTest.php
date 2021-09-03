<?php

namespace Tests\unit\Jobs\Instance;

use App\Jobs\Instance\VolumeGroupAttachDetach;
use App\Models\V2\Task;
use App\Models\V2\Volume;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\Mocks\Resources\VolumeMock;
use Tests\TestCase;

class VolumeGroupAttachDetachTest extends TestCase
{
    use VolumeGroupMock, VolumeMock;

    /** @test */
    public function backoffIfInstanceIsNotComplete()
    {
        Log::partialMock()
            ->expects('warning')
            ->withSomeOfArgs('Instance not in sync, retrying in 5 seconds')
            ->once();

        $this->instance()->volume_group_id = $this->volumeGroup()->id;
        $this->instance()->saveQuietly();

        Model::withoutEvents(function () {
            $sync = new Task([
                'id' => 'sync-1',
                'completed' => false,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $sync->resource()->associate($this->instance());
            $sync->save();
        });

        $this->assertEmpty((new VolumeGroupAttachDetach($this->instance()))->handle());
    }

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
        $this->instance()->volume_group_id = $this->volumeGroup()->id;
        $this->instance()->volumes()->attach($this->volume());
        $this->instance()->saveQuietly();

        $this->assertEmpty((new VolumeGroupAttachDetach($this->instance()))->handle());
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
        $this->instance()->volume_group_id = $this->volumeGroup()->id;
        $this->instance()->saveQuietly();

        // kingpin mocks
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/instance/i-test')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['volumes' => []]));
            });

        $this->kingpinServiceMock()
            ->expects('post')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/volume/attach',
                [
                    'json' => [
                        'volumeUUID' => $this->volume()->vmware_uuid,
                        'shared' => $this->volume()->is_shared,
                        'unitNumber' => $this->volume()->port
                    ]
                ]
            ])->andReturnTrue();

        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/instance/i-test/volume/'.$this->volume()->vmware_uuid)
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['iops' => $this->volume()->iops]));
            });

        // Assert volume is not currently attached
        $this->assertEquals(0, $this->instance()->volumes()->where('id', '=', $this->volume()->id)->count());

        (new VolumeGroupAttachDetach($this->instance()))->handle();

        $this->instance()->refresh();

        // assert volume is now attached
        $this->assertEquals(1, $this->instance()->volumes()->where('id', '=', $this->volume()->id)->count());
    }

    /** @test */
    public function volumeDetachesSuccessfully()
    {
        $this->volume()->is_shared = true;
        $this->volume()->port = 0;
        $this->volume()->saveQuietly();
        $this->instance()->volumes()->attach($this->volume());
        $this->instance()->saveQuietly();

        // kingpin mocks
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/instance/i-test')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['volumes' => []]));
            });

        (new VolumeGroupAttachDetach($this->instance()))->handle();

        $this->assertEquals(0, $this->instance()->volumes()->count());
    }
}
