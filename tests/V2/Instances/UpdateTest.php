<?php

namespace Tests\V2\Instances;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    protected $vpc;

    protected $appliance;

    protected $appliance_version;

    protected $instance;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        Vpc::flushEventListeners();
        $this->vpc = factory(Vpc::class)->create([
            'name' => 'Manchester Vpc',
        ]);
        $this->appliance = factory(Appliance::class)->create([
            'appliance_name' => 'Test Appliance',
        ])->refresh();
        $this->appliance_version = factory(ApplianceVersion::class)->create([
            'appliance_version_appliance_id' => $this->appliance->appliance_id,
        ])->refresh();
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'name' => 'UpdateTest Default',
            'appliance_id' => $this->appliance_version->getKey(),
            'vcpu_tier' => $this->faker->uuid,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
        ]);
    }

    public function testNoPermsIsDenied()
    {
        $data = [
            'vpc_id' => $this->vpc->getKey(),
        ];

        $this->patch(
            '/v2/instances/' . $this->instance->getKey(),
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

    public function testNonExistentNetworkId()
    {
        $data = [
            'vpc_id' => 'vpc-12345'
        ];

        $this->patch(
            '/v2/instances/' . $this->instance->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'No valid Vpc record found for specified vpc id',
                'status' => 422,
                'source' => 'vpc_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testInvalidApplianceIdFails()
    {
        $data = [
            'appliance_id' => $this->faker->uuid,
        ];
        $this->patch(
            '/v2/instances/' . $this->instance->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
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
            'vcpu_cores' => 0,
        ];
        $this->patch(
            '/v2/instances/' . $this->instance->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The vcpu cores field must be greater than or equal to one',
                'status' => 422,
                'source' => 'vcpu_cores'
            ])
            ->assertResponseStatus(422);
    }

    public function testRamCapacityLessThan1024Fails()
    {
        $data = [
            'ram_capacity' => 1,
        ];
        $this->patch(
            '/v2/instances/' . $this->instance->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The ram capacity field must be greater than or equal to 1024 megabytes',
                'status' => 422,
                'source' => 'ram_capacity'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $vpc = factory(Vpc::class)->create([
            'name' => 'Manchester Network',
        ]);

        $data = [
            'vpc_id' => $vpc->getKey(),
        ];
        $this->patch(
            '/v2/instances/' . $this->instance->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(200);

        $instance = Instance::findOrFail($this->instance->getKey());
        $this->assertEquals($data['vpc_id'], $instance->vpc_id);
    }
}
