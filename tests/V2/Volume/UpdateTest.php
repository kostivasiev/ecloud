<?php

namespace Tests\V2\Volume;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
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
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->kingpinServiceMock()
            ->shouldReceive('post')
            ->withSomeOfArgs(
                '/api/v2/vpc/vpc-test/volume',
                [
                    'json' => [
                        'volumeId' => 'vol-abc123xyz',
                        'sizeGiB' => '100',
                        'shared' => false
                    ]
                ]
            )
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['uuid' => '7b9d062f-2048-42e8-82f9-f67d3e9e3dfe']));
            });
        $this->volume = factory(Volume::class)->create([
            'id' => 'vol-abc123xyz',
            'vpc_id' => $this->vpc()->id
        ]);
    }

    public function testNotOwnedVolumeIsFailed()
    {
        $this->be(new Consumer(2, [config('app.name') . '.read', config('app.name') . '.write']));
        $data = [
            'name' => 'Volume 1',
        ];

        $this->patch(
            '/v2/volumes/' . $this->volume->id,
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
            '/v2/volumes/' . $this->volume->id,
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
            '/v2/volumes/' . $this->volume->id,
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
        $this->kingpinServiceMock()
            ->shouldReceive('put')
            ->withSomeOfArgs(
                '/api/v2/vpc/vpc-test/volume/7b9d062f-2048-42e8-82f9-f67d3e9e3dfe/size',
                [
                    'json' => [
                        'sizeGiB' => '1999'
                    ]
                ]
            )->andReturnUsing(function () {
                return new Response(200);
            });
        $data = [
            'name' => 'Volume 1',
            'capacity' => (config('volume.capacity.max') - 1),
        ];

        $this->patch(
            '/v2/volumes/' . $this->volume->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(202);

        $volumeId = (json_decode($this->response->getContent()))->data->id;
        $volume = Volume::find($volumeId);
        $this->assertNotNull($volume);
    }
}
