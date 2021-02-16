<?php

namespace Tests\V2\Volume;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    /** @var Region */
    private $region;

    /**
     * @var AvailabilityZone
     */
    private $availabilityZone;

    /** @var Vpc */
    private $vpc;

    /** @var Volume */
    private $volume;

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
        $this->volume->setSyncCompleted();
    }

    public function testInvalidVpcIdIsFailed()
    {
        $data = [
            'name' => 'Volume 1',
            'vpc_id' => 'x',
            'availability_zone_id' => $this->availabilityZone->getKey()
        ];

        $this->patch(
            '/v2/volumes/' . $this->volume->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '1-0',
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

        $data = [
            'name' => 'Volume 1',
            'vpc_id' => $this->vpc->getKey(),
            'availability_zone_id' => $availabilityZone->getKey(),
            'capacity' => (config('volume.capacity.max') - 1),
        ];

        $this->patch(
            '/v2/volumes/' . $this->volume->getKey(),
            $data,
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

    public function testNotOwnedVolumeIsFailed()
    {
        $data = [
            'name' => 'Volume 1',
            'vpc_id' => $this->vpc->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey()
        ];

        $this->patch(
            '/v2/volumes/' . $this->volume->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '2-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Not found',
                'detail' => 'No Volume with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testMinCapacityValidation()
    {
        $data = [
            'name' => 'Volume 1',
            'vpc_id' => $this->vpc->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey(),
            'capacity' => (config('volume.capacity.min') - 1),
        ];

        $this->patch(
            '/v2/volumes/' . $this->volume->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'specified capacity is below the minimum of ' . config('volume.capacity.min'),
                'status' => 422,
                'source' => 'capacity'
            ])
            ->assertResponseStatus(422);
    }

    public function testMaxCapacityValidation()
    {
        $data = [
            'name' => 'Volume 1',
            'vpc_id' => $this->vpc->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey(),
            'capacity' => (config('volume.capacity.max') + 1),
        ];

        $this->patch(
            '/v2/volumes/' . $this->volume->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'specified capacity is above the maximum of ' . config('volume.capacity.max'),
                'status' => 422,
                'source' => 'capacity'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'name' => 'Volume 1',
            'vpc_id' => $this->vpc->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey(),
            'capacity' => (config('volume.capacity.max') - 1),
        ];

        $this->patch(
            '/v2/volumes/' . $this->volume->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(200);

        $volumeId = (json_decode($this->response->getContent()))->data->id;
        $volume = Volume::find($volumeId);
        $this->assertNotNull($volume);
    }
}
