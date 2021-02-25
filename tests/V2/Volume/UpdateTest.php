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
        $this->volume = factory(Volume::class)->create([
            'vpc_id' => $this->vpc()->getKey()
        ]);
    }

    public function testNotOwnedVolumeIsFailed()
    {
        $data = [
            'name' => 'Volume 1',
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
