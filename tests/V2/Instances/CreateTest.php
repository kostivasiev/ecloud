<?php

namespace Tests\V2\Instances;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $availability_zone;
    protected $instance;
    protected $region;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        Vpc::flushEventListeners();
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
    }

    public function testNotOwnedNetworkIdIsFailed()
    {
        $this->post(
            '/v2/instances',
            [
                'vpc_id' => $this->vpc->getKey(),
            ],
            [
                'X-consumer-custom-id' => '2-0',
                'X-consumer-groups'    => 'ecloud.write',
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

    public function testValidDataSucceeds()
    {
        // No name defined - defaults to ID
        $this->post(
            '/v2/instances',
            [
                'vpc_id' => $this->vpc->getKey(),
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);

        $id = (json_decode($this->response->getContent()))->data->id;
        $this->seeJson([
            'id' => $id
        ])
            ->seeInDatabase(
                'instances',
                [
                    'id'   => $id,
                    'name' => $id,
                ],
                'ecloud'
            );

        // Name defined
        $name = $this->faker->word();

        $this->post(
            '/v2/instances',
            [
                'vpc_id' => $this->vpc->getKey(),
                'name'   => $name
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);

        $id = (json_decode($this->response->getContent()))->data->id;
        $this->seeInDatabase(
            'instances',
            [
                    'id'   => $id,
                    'name' => $name,
                ],
            'ecloud'
        );
    }

    public function testAvailabilityZoneIdAutoPopulated()
    {
        $this->post(
            '/v2/instances',
            [
                'vpc_id' => $this->vpc->getKey(),
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);

        $id = (json_decode($this->response->getContent()))->data->id;
        $instance = Instance::findOrFail($id);
        $this->assertNotNull($instance->availability_zone_id);
    }
}
