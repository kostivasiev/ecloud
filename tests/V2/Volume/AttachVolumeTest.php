<?php

namespace Tests\V2\Volume;

use App\Events\V2\Task\Created;
use App\Models\V2\Task;
use App\Models\V2\Volume;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AttachVolumeTest extends TestCase
{
    public function testAttachingVolume()
    {
        Event::fake([Created::class]);

        $volume = Volume::factory()->create([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);

        // Attach a volume
        $this->post('/v2/volumes/' . $volume->id . '/attach', [
            'instance_id' => $this->instanceModel()->id,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(202);
    }

    public function testAttachVolumeInstanceHasFailed()
    {
        $volume = Volume::factory()->create([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);

        // Force failure
        Model::withoutEvents(function () {
            $model = new Task([
                'id' => 'sync-test',
                'failure_reason' => 'Unit Test Failure',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $model->resource()->associate($this->instanceModel());
            $model->save();
        });

        // Attach a volume
        $this->post('/v2/volumes/' . $volume->id . '/attach', [
            'instance_id' => $this->instanceModel()->id,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The specified instance id resource currently has the status of \'failed\' and cannot be used',
            ]
        )->assertResponseStatus(422);
    }

    public function testAttachingAttachedVolumeFails()
    {
        Event::fake([Created::class]);

        $volume = Volume::factory()->create([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);

        $this->instanceModel()->volumes()->attach($volume);

        $this->post('/v2/volumes/' . $volume->id . '/attach', [
            'instance_id' => $this->instanceModel()->id,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'errors' => [
                [
                    'title' => 'Validation Error',
                    'detail' => 'The specified volume is already attached to this instance',
                    'status' => 422,
                    'source' => 'instance_id',
                ]
            ],
        ])->assertResponseStatus(422);
    }
}
