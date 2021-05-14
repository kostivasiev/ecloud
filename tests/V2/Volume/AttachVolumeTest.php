<?php

namespace Tests\V2\Volume;

use App\Events\V2\Task\Created;
use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AttachVolumeTest extends TestCase
{
    public function testAttachingVolume()
    {
        Event::fake([Created::class]);

        $volume = factory(Volume::class)->create([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);

        // Attach a volume
        $this->post('/v2/volumes/' . $volume->id . '/attach', [
            'instance_id' => $this->instance()->id,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(202);
    }
    public function testAttachingAttachedVolumeFails()
    {
        Event::fake([Created::class]);

        $volume = factory(Volume::class)->create([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);

        $this->instance()->volumes()->attach($volume);

        $this->post('/v2/volumes/' . $volume->id . '/attach', [
            'instance_id' => $this->instance()->id,
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
