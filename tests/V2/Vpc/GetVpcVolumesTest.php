<?php

namespace Tests\V2\Vpc;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetVpcVolumesTest extends TestCase
{
    use DatabaseMigrations;

    public AvailabilityZone $availabilityZone;
    public Region $region;
    public $volumes;
    public Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();

        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id
        ]);

        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->id,
        ]);

        $this->volumes = factory(Volume::class, 4)->create([
            'name' => 'Volume ' . uniqid(),
            'vpc_id' => $this->vpc->id,
            'availability_zone_id' => $this->availabilityZone->id
        ]);
    }

    public function testVolumesCollection()
    {
        $this->get(
            '/v2/vpcs/'.$this->vpc->id.'/volumes',
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->volumes[0]->id,
                'name' => $this->volumes[0]->name,
                'vpc_id' => $this->volumes[0]->vpc_id,
                'availability_zone_id' => $this->volumes[0]->availability_zone_id,
                'capacity' => $this->volumes[0]->capacity,
            ])
            ->assertResponseStatus(200);
    }
}
