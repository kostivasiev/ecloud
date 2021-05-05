<?php

namespace Tests\V2\Volume;

use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class InstanceTest extends TestCase
{
    public function testGetInstances()
    {
        $volume = Model::withoutEvents(function() {
            return factory(Volume::class)->create([
                'id' => 'vol-test',
                'name' => 'Volume',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);
        });


        $volume->instances()->attach($this->instance());

        $this->get('/v2/volumes/' . $volume->id . '/instances', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->instance()->id,
        ])->assertResponseStatus(200);
    }
}
