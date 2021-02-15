<?php

namespace Tests\V2\Volume;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    protected $volume;

    public function setUp(): void
    {
        parent::setUp();
        $this->availabilityZone();
        $this->volume = factory(Volume::class)->create([
            'vpc_id' => $this->vpc()->getKey()
        ]);
    }

    public function testNotOwnedVpcIdIsFailed()
    {
        $this->post(
            '/v2/volumes',
            [
                'name' => 'Volume 1',
                'vpc_id' => $this->vpc()->getKey(),
                'availability_zone_id' => $this->availabilityZone()->getKey()
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
                'vpc_id' => $this->vpc()->getKey(),
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
        $this->kingpinServiceMock()
            ->expects('post')
            ->withSomeOfArgs('/api/v1/vpc/' . $this->vpc()->getKey() . '/volume')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['uuid' => '3c011de3-f5c8-4195-8ba2-651436ad6486']));
            });
        $this->post(
            '/v2/volumes',
            [
                'name' => 'Volume 1',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'capacity' => (config('volume.capacity.min') + 1),
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(201);

        $volumeId = (json_decode($this->response->getContent()))->data->id;
        $volume = Volume::find($volumeId);
        $this->assertNotNull($volume);
    }

    public function testAzIsOptionalParameter()
    {
        $this->kingpinServiceMock()
            ->expects('post')
            ->withSomeOfArgs('/api/v1/vpc/' . $this->vpc()->getKey() . '/volume')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['uuid' => '3c011de3-f5c8-4195-8ba2-651436ad6486']));
            });

        $this->post(
            '/v2/volumes',
            [
                'name' => 'Volume 1',
                'vpc_id' => $this->vpc()->getKey(),
                'capacity' => (config('volume.capacity.min') + 1),
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(201);

        $volumeId = (json_decode($this->response->getContent()))->data->id;
        $volume = Volume::find($volumeId);
        $this->assertNotNull($volume);
        $this->assertNotNull($volume->availability_zone_id);
    }
}
