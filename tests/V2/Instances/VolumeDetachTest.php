<?php

namespace Tests\V2\Instances;

use App\Events\V2\Task\Created;
use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class VolumeDetachTest extends TestCase
{
    public function testSucceeds()
    {
        $volume = Volume::factory()->create([
            'id' => 'vol-test',
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id
        ]);

        $this->instanceModel()->volumes()->attach($volume);

        Event::fake([Created::class]);

        $this->post(
            '/v2/instances/' . $this->instanceModel()->id . '/volume-detach',
            [
                'volume_id' => $volume->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(202);
    }

    public function testNotAttachedFails()
    {
        $volume = Volume::factory()->create([
            'id' => 'vol-test',
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id
        ]);

        Event::fake([Created::class]);

        $this->post(
            '/v2/instances/' . $this->instanceModel()->id . '/volume-detach',
            [
                'volume_id' => $volume->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(422);
    }
}
