<?php

namespace Tests\V2\Instances;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    protected $instance;

    protected $appliance;

    protected $appliance_version;

    protected $vpc;

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
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'name' => 'GetTest Default',
            'appliance_version_id' => $this->appliance_version->getKey(),
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
        ]);
    }

    public function testNoPermsIsDenied()
    {
        $this->get(
            '/v2/instances',
            []
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/instances',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->instance->getKey(),
                'name' => $this->instance->name,
                'vpc_id' => $this->instance->vpc_id,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/instances/' . $this->instance->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->instance->getKey(),
                'name' => $this->instance->name,
                'vpc_id' => $this->instance->vpc_id,
                'appliance_version_id' => $this->appliance_version->appliance_version_uuid,
            ])
            ->assertResponseStatus(200);

        $result = json_decode($this->response->getContent());

        // Test to ensure appliance_id as a UUID is in the returned result
        $this->assertEquals($this->appliance->appliance_uuid, $result->data->appliance_id);
    }
}
