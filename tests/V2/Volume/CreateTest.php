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
            'region_id' => $this->region->id
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->id
        ]);
        $this->volume = factory(Volume::class)->create([
            'vpc_id' => $this->vpc->id
        ]);
    }

    public function testNotOwnedVpcIdIsFailed()
    {
        // TODO: Endpoint disabled until we do the volumes milestone
        $this->markTestSkipped('Create volume tests skipped until we do the volumes milestone');

        $this->post(
            '/v2/volumes',
            [
                'name' => 'Volume 1',
                'vpc_id' => $this->vpc->id,
                'availability_zone_id' => $this->availabilityZone->id
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
        // TODO: Endpoint disabled until we do the volumes milestone
        $this->markTestSkipped('Create volume tests skipped until we do the volumes milestone');

        $region = factory(Region::class)->create();
        $availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $region->id
        ]);

        $this->post(
            '/v2/volumes',
            [
                'name' => 'Volume 1',
                'vpc_id' => $this->vpc->id,
                'availability_zone_id' => $availabilityZone->id,
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
        // TODO: Endpoint disabled until we do the volumes milestone
        $this->markTestSkipped('Create volume tests skipped until we do the volumes milestone');

        $this->post(
            '/v2/volumes',
            [
                'name' => 'Volume 1',
                'vpc_id' => $this->vpc->id,
                'availability_zone_id' => $this->availabilityZone->id,
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
        // TODO: Endpoint disabled until we do the volumes milestone
        $this->markTestSkipped('Create volume tests skipped until we do the volumes milestone');

        $this->post(
            '/v2/volumes',
            [
                'name' => 'Volume 1',
                'vpc_id' => $this->vpc->id,
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
