<?php

namespace Tests\V2\Instances;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Network;
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
    protected $network;
    protected $region;
    protected $vpc;
    protected $appliance;
    protected $appliance_version;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->appliance = factory(Appliance::class)->create([
            'appliance_name' => 'Test Appliance',
        ])->refresh();  // Hack needed since this is a V1 resource
        $this->appliance_version = factory(ApplianceVersion::class)->create([
            'appliance_version_appliance_id' => $this->appliance->appliance_id,
        ])->refresh();  // Hack needed since this is a V1 resource
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->id,
            'appliance_version_id' => $this->appliance_version->uuid,
            'availability_zone_id' => $this->availability_zone->getKey(),
        ]);
        $this->network = factory(Network::class)->create();
    }

    public function testValidDataSucceedsWithoutName()
    {
        // No name defined - defaults to ID
        $this->post('/v2/instances', [
            'vpc_id' => $this->vpc->getKey(),
            'appliance_id' => $this->appliance->uuid,
            'network_id' => $this->network->id,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(201);

        $id = (json_decode($this->response->getContent()))->data->id;
        $this->seeJson([
            'id' => $id
        ])->seeInDatabase('instances', [
            'id' => $id,
            'name' => $id,
        ], 'ecloud');
    }

    public function testValidDataSucceedsWithName()
    {
        // Name defined
        $name = $this->faker->word();

        $this->post(
            '/v2/instances',
            [
                'name' => $name,
                'vpc_id' => $this->vpc->getKey(),
                'appliance_id' => $this->appliance->uuid,
                'network_id' => $this->network->id,
                'vcpu_cores' => 1,
                'ram_capacity' => 1024,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);

        $id = (json_decode($this->response->getContent()))->data->id;
        $this->seeInDatabase(
            'instances',
            [
                'id' => $id,
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
                'appliance_id' => $this->appliance->uuid,
                'network_id' => $this->network->id,
                'vcpu_cores' => 1,
                'ram_capacity' => 1024,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);

        $id = (json_decode($this->response->getContent()))->data->id;
        $instance = Instance::findOrFail($id);
        $this->assertNotNull($instance->availability_zone_id);
    }

    public function testSettingApplianceVersionId()
    {
        // No name defined - defaults to ID
        $data = [
            'vpc_id' => $this->vpc->getKey(),
            'appliance_id' => $this->appliance->uuid,
            'network_id' => $this->network->id,
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

        $id = json_decode($this->response->getContent())->data->id;
        $instance = Instance::findOrFail($id);
        // Check that the appliance id has been converted to the appliance version id
        $this->assertEquals($this->appliance_version->uuid, $instance->appliance_version_id);
    }
}
