<?php

namespace Tests\V2\Vpc;

use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetVpcVolumesTest extends TestCase
{
    public $volumes;

    public function setUp(): void
    {
        parent::setUp();

        $this->volume = factory(Volume::class)->create([
            'id' => 'vol-test',
            'name' => 'Volume',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);
    }

    public function testVolumesCollection()
    {
        $this->get('/v2/vpcs/' . $this->vpc()->id . '/volumes', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => 'vol-test',
            'name' => 'Volume',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => '100',
        ])->assertResponseStatus(200);
    }
}
