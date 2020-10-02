<?php

namespace Tests\V2\Volume;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    protected $availabilityZone;
    protected $region;
    protected $volume;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();

        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->volume = factory(Volume::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);
    }

    public function testNotOwnedVpcIdIsFailed()
    {
        $this->post(
            '/v2/volumes',
            [
                'name' => 'Volume 1',
                'vpc_id' => $this->vpc->getKey(),
                'availability_zone_id' => $this->availabilityZone->getKey()
            ],
            [
                'X-consumer-custom-id' => '2-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The specified vpc id was not found',
                'status' => 422,
                'source' => 'vpc_id'
            ])
            ->assertResponseStatus(422);
    }


    public function testInvalidAzIsFailed()
    {
        $region = factory(Region::class)->create();
        $availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $region->getKey()
        ]);

        $this->post(
            '/v2/volumes',
            [
                'name' => 'Volume 1',
                'vpc_id' => $this->vpc->getKey(),
                'availability_zone_id' => $availabilityZone->getKey(),
                'capacity' => (config('volume.capacity.min') + 1),
            ],
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Not Found',
                'detail' => 'The specified availability zone is not available to that VPC',
                'status' => 404,
                'source' => 'availability_zone_id'
            ])
            ->assertResponseStatus(404);
    }

    public function testValidDataSucceeds()
    {
        $this->post(
            '/v2/volumes',
            [
                'name' => 'Volume 1',
                'vpc_id' => $this->vpc->getKey(),
                'availability_zone_id' => $this->availabilityZone->getKey(),
                'capacity' => (config('volume.capacity.min') + 1),
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);

        $volumeId = (json_decode($this->response->getContent()))->data->id;
        $volume = Volume::find($volumeId);
        $this->assertNotNull($volume);
    }

    public function testAzIsOptionalParameter()
    {
        $this->post(
            '/v2/volumes',
            [
                'name' => 'Volume 1',
                'vpc_id' => $this->vpc->getKey(),
                'capacity' => (config('volume.capacity.min') + 1),
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);

        $volumeId = (json_decode($this->response->getContent()))->data->id;
        $volume = Volume::find($volumeId);
        $this->assertNotNull($volume);
        $this->assertNotNull($volume->availability_zone_id);
    }
}
