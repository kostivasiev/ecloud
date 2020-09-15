<?php

namespace Tests\V2\Volume;

use App\Models\V2\Region;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    /** @var Region */
    private $region;

    /** @var Vpc */
    private $vpc;

    /** @var Volume */
    private $volume;

    public function setUp(): void
    {
        parent::setUp();

        $this->region = factory(Region::class)->create();
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->volume = factory(Volume::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);
    }

    public function testInvalidVpcIdIsFailed()
    {
        $data = [
            'name' => 'Volume 1',
            'vpc_id' => 'x',
        ];

        $this->post(
            '/v2/volumes',
            $data,
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The specified vpc id was not found',
                'status' => 422,
                'source' => 'vpc_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testNotOwnedVpcIdIsFailed()
    {
        $data = [
            'name'    => 'Volume 1',
            'vpc_id' => $this->vpc->getKey(),
        ];

        $this->post(
            '/v2/volumes',
            $data,
            [
                'X-consumer-custom-id' => '2-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The specified vpc id was not found',
                'status' => 422,
                'source' => 'vpc_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testMinCapacityValidation()
    {
        $data = [
            'name'    => 'Volume 1',
            'vpc_id' => $this->vpc->getKey(),
            'capacity' => (config('volume.capacity.min')-1),
        ];

        $this->post(
            '/v2/volumes',
            $data,
            [
                'X-consumer-custom-id' => '2-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'specified capacity is below the minimum of ' . config('volume.capacity.min'),
                'status' => 422,
                'source' => 'capacity'
            ])
            ->assertResponseStatus(422);
    }

    public function testMaxCapacityValidation()
    {
        $data = [
            'name'    => 'Volume 1',
            'vpc_id' => $this->vpc->getKey(),
            'capacity' => (config('volume.capacity.max')+1),
        ];

        $this->post(
            '/v2/volumes',
            $data,
            [
                'X-consumer-custom-id' => '2-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'specified capacity is above the maximum of ' . config('volume.capacity.max'),
                'status' => 422,
                'source' => 'capacity'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'name'    => 'Volume 1',
            'vpc_id' => $this->vpc->getKey(),
            'capacity' => (config('volume.capacity.min')+1),
        ];

        $this->post(
            '/v2/volumes',
            $data,
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

}
