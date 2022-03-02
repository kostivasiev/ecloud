<?php

namespace Tests\V2\Volume;

use App\Models\V2\Volume;
use Tests\TestCase;

class InstanceTest extends TestCase
{
    public function testGetInstances()
    {
        $volume = Volume::factory()->createOne([
            'id' => 'vol-test',
            'name' => 'Volume',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);

        $volume->instances()->attach($this->instanceModel());

        $this->get('/v2/volumes/' . $volume->id . '/instances', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->instanceModel()->id,
        ])->assertResponseStatus(200);
    }
}
