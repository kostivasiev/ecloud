<?php

namespace Tests\V2\Vpc;

use App\Models\V2\Volume;
use Tests\TestCase;

class GetVpcVolumesTest extends TestCase
{
    public $volumes;

    public function setUp(): void
    {
        parent::setUp();

        Volume::factory()->create([
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
        ])->assertJsonFragment([
            'id' => 'vol-test',
            'name' => 'Volume',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => 100,
        ])->assertStatus(200);
    }
}
