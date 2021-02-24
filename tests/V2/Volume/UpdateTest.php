<?php

namespace Tests\V2\Volume;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    private $volume;

    public function setUp(): void
    {
        parent::setUp();

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v1/vpc/vpc-test/volume',
                [
                    'json' => [
                        'volumeId' => 'vol-test',
                        'sizeGiB' => '100',
                        'shared' => false,
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['uuid' => 'uuid-test-uuid-test-uuid-test']));
            });

        $this->volume = factory(Volume::class)->create([
            'id' => 'vol-test',
            'name' => 'Volume',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);
    }

    public function testInvalidVpcIdIsFailed()
    {
        $this->patch('/v2/volumes/' . $this->volume->id, [
            'name' => 'Volume 1',
            'vpc_id' => 'x',
            'availability_zone_id' => $this->availabilityZone()->id
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The specified vpc id was not found',
            'status' => 422,
            'source' => 'vpc_id'
        ])->assertResponseStatus(422);
    }


    public function testInvalidAzIsFailed()
    {
        $region = factory(Region::class)->create();
        $availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $region->id
        ]);

        $this->patch('/v2/volumes/' . $this->volume->id, [
            'name' => 'Volume 1',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $availabilityZone->id,
            'capacity' => (config('volume.capacity.max') - 1),
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Not Found',
            'detail' => 'The specified availability zone is not available to that VPC',
            'status' => 404,
            'source' => 'availability_zone_id'
        ])->assertResponseStatus(404);
    }

    public function testNotOwnedVolumeIsFailed()
    {
        $this->patch('/v2/volumes/' . $this->volume->id, [
            'name' => 'Volume 1',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id
        ], [
            'X-consumer-custom-id' => '2-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Not found',
            'detail' => 'No Volume with that ID was found',
            'status' => 404,
        ])->assertResponseStatus(404);
    }

    public function testMinCapacityValidation()
    {
        $this->patch('/v2/volumes/' . $this->volume->id, [
            'name' => 'Volume 1',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => (config('volume.capacity.min') - 1),
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Validation Error',
            'detail' => 'specified capacity is below the minimum of ' . config('volume.capacity.min'),
            'status' => 422,
            'source' => 'capacity'
        ])->assertResponseStatus(422);
    }

    public function testMaxCapacityValidation()
    {
        $this->patch('/v2/volumes/' . $this->volume->id, [
            'name' => 'Volume 1',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => (config('volume.capacity.max') + 1),
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Validation Error',
            'detail' => 'specified capacity is above the maximum of ' . config('volume.capacity.max'),
            'status' => 422,
            'source' => 'capacity'
        ])->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        $this->kingpinServiceMock()->expects('put')
            ->withArgs([
                '/api/v1/vpc/vpc-test/volume/uuid-test-uuid-test-uuid-test/size',
                [
                    'json' => [
                        'sizeGiB' => '999',
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->patch('/v2/volumes/' . $this->volume->id, [
            'name' => 'Volume 1',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => (config('volume.capacity.max') - 1),
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(200);

        $volumeId = (json_decode($this->response->getContent()))->data->id;
        $volume = Volume::find($volumeId);
        $this->assertNotNull($volume);
    }
}
