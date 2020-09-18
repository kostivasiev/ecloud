<?php

namespace Tests\V2\Instances;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;
    protected $vpc;
    protected $appliance;
    protected $appliance_version;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        Vpc::flushEventListeners();
        $this->vpc = factory(Vpc::class)->create([
            'name' => 'Manchester VPC',
        ]);
        $this->appliance = factory(Appliance::class)->create([
            'appliance_name' => 'Test Appliance',
        ])->refresh();
        $this->appliance_version = factory(ApplianceVersion::class)->create([
            'appliance_version_appliance_id' => $this->appliance->appliance_id,
        ])->refresh();
    }

    public function testNoPermsIsDenied()
    {
        $data = [
            'vpc_id' => $this->vpc->getKey(),
        ];
        $this->post(
            '/v2/instances',
            $data,
            []
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testNotOwnedVpcIdIsFailed()
    {
        $data = [
            'vpc_id' => $this->vpc->getKey(),
            'appliance_id' => $this->appliance_version->getKey(),
            'vcpu_tier' => $this->faker->uuid,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
        ];
        $this->post(
            '/v2/instances',
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

    public function testNonExistentApplianceIsFailed()
    {
        $data = [
            'vpc_id' => $this->vpc->getKey(),
            'appliance_id' => $this->faker->uuid,
            'vcpu_tier' => $this->faker->uuid,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
        ];
        $this->post(
            '/v2/instances',
            $data,
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The appliance id is not a valid Appliance',
                'status' => 422,
                'source' => 'appliance_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testInvalidVcpuCountFails()
    {
        $data = [
            'vpc_id' => $this->vpc->getKey(),
            'appliance_id' => $this->appliance_version->getKey(),
            'vcpu_tier' => $this->faker->uuid,
            'vcpu_cores' => 0,
            'ram_capacity' => 1024,
        ];
        $this->post(
            '/v2/instances',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'Specified vcpu cores is below the minimum of ' . config('cpu.cores.min'),
                'status' => 422,
                'source' => 'vcpu_cores'
            ])
            ->assertResponseStatus(422);
    }

    public function testRamCapacityLessThan1024Fails()
    {
        $data = [
            'vpc_id' => $this->vpc->getKey(),
            'appliance_id' => $this->appliance_version->getKey(),
            'vcpu_tier' => $this->faker->uuid,
            'vcpu_cores' => 1,
            'ram_capacity' => 1,
        ];
        $this->post(
            '/v2/instances',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'Specified ram capacity is below the minimum of ' . config('ram.capacity.min'),
                'status' => 422,
                'source' => 'ram_capacity'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        // No name defined - defaults to ID
        $data = [
            'vpc_id' => $this->vpc->getKey(),
            'appliance_id' => $this->appliance_version->getKey(),
            'vcpu_tier' => $this->faker->uuid,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
        ];
        $this->post(
            '/v2/instances',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
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
                'id' => $id,
                'name' => $id,
                ],
                'ecloud'
            );

        // Name defined
        $name = $this->faker->word();
        $data = [
            'vpc_id' => $this->vpc->getKey(),
            'name' => $name,
            'appliance_id' => $this->appliance_version->getKey(),
            'vcpu_tier' => $this->faker->uuid,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
        ];

        $this->post(
            '/v2/instances',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);

        $id = (json_decode($this->response->getContent()))->data->id;
        $this->seeJson([
            'id' => $id,
        ])
            ->seeInDatabase(
                'instances',
                [
                    'id' => $id,
                    'name' => $name,
                ],
                'ecloud'
            );
    }
}
