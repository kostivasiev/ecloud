<?php

namespace Tests\V2\Instances;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\ApplianceVersionData;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Admin\Devices\AdminClient;

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
    protected $applianceVersion;
    protected $image;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->id
        ]);
        $this->appliance = factory(Appliance::class)->create([
            'appliance_name' => 'Test Appliance',
        ])->refresh();  // Hack needed since this is a V1 resource
        $this->applianceVersion = factory(ApplianceVersion::class)->create([
            'appliance_version_appliance_id' => $this->appliance->appliance_id,
        ])->refresh();  // Hack needed since this is a V1 resource
        $this->image = factory(Image::class)->create([
            'appliance_version_id' => $this->applianceVersion->appliance_version_uuid,
        ])->refresh();
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->id,
            'image_id' => $this->image->id,
            'availability_zone_id' => $this->availability_zone->id,
        ]);
        $mockAdminDevices = \Mockery::mock(AdminClient::class)
            ->shouldAllowMockingProtectedMethods();
        app()->bind(AdminClient::class, function () use ($mockAdminDevices) {
            $mockedResponse = new \stdClass();
            $mockedResponse->category = "Linux";
            $mockAdminDevices->shouldReceive('licenses->getById')->andReturn($mockedResponse);
            return $mockAdminDevices;
        });
        $this->network = factory(Network::class)->create();
    }

    public function testValidDataSucceedsWithoutName()
    {
        // No name defined - defaults to ID
        $this->post('/v2/instances', [
            'vpc_id' => $this->vpc->id,
            'image_id' => $this->image->id,
            'network_id' => $this->network->id,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
            'volume_iops' => 600,
            'backup_enabled' => true,
            'host_group_id' => $this->hostGroup()->id,
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
            'backup_enabled' => 1,
            'host_group_id' => $this->hostGroup()->id,
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
                'vpc_id' => $this->vpc->id,
                'image_id' => $this->image->id,
                'network_id' => $this->network->id,
                'vcpu_cores' => 1,
                'ram_capacity' => 1024,
                'volume_iops' => 600,
                'backup_enabled' => true,
                'host_group_id' => $this->hostGroup()->id,
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
                'backup_enabled' => 1,
                'host_group_id' => $this->hostGroup()->id,
            ],
            'ecloud'
        );
    }

    public function testAvailabilityZoneIdAutoPopulated()
    {
        $this->post(
            '/v2/instances',
            [
                'vpc_id' => $this->vpc->id,
                'image_id' => $this->image->id,
                'network_id' => $this->network->id,
                'vcpu_cores' => 1,
                'ram_capacity' => 1024,
                'volume_iops' => 600,
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

    public function testApplianceSpecDefaultConfigFallbacks()
    {
        $data = [
            'vpc_id' => $this->vpc->id,
            'image_id' => $this->image->id,
            'network_id' => $this->network->id,
            'vcpu_cores' => 11,
            'ram_capacity' => 512,
            'volume_capacity' => 10,
            'volume_iops' => 600,
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
                'title' => 'Validation Error',
                'detail' => 'Specified vcpu cores is above the maximum of ' . config('instance.cpu_cores.max'),
                'status' => 422,
                'source' => 'ram_capacity'
            ])
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'Specified ram capacity is below the minimum of ' . config('instance.ram_capacity.min'),
                'status' => 422,
                'source' => 'ram_capacity'
            ])
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'Specified volume capacity is below the minimum of ' . config('volume.capacity.linux.min'),
                'status' => 422,
                'source' => 'ram_capacity'
            ])
            ->assertResponseStatus(422);

        //dd($this->response->getContent());
    }

    public function testApplianceSpecRamMin()
    {
        factory(ApplianceVersionData::class)->create([
            'key' => 'ukfast.spec.ram.min',
            'value' => 2048,
            'appliance_version_uuid' => $this->applianceVersion->appliance_version_uuid,
        ]);

        $data = [
            'vpc_id' => $this->vpc->id,
            'image_id' => $this->image->id,
            'network_id' => $this->network->id,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
            'volume_capacity' => 30,
            'volume_iops' => 600,
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
                'title' => 'Validation Error',
                'detail' => 'Specified ram capacity is below the minimum of 2048',
                'status' => 422,
                'source' => 'ram_capacity'
            ])->assertResponseStatus(422);
    }

    public function testApplianceSpecVolumeMin()
    {
        factory(ApplianceVersionData::class)->create([
            'key' => 'ukfast.spec.volume.min',
            'value' => 50,
            'appliance_version_uuid' => $this->applianceVersion->appliance_version_uuid,
        ]);

        $data = [
            'vpc_id' => $this->vpc->id,
            'image_id' => $this->image->id,
            'network_id' => $this->network->id,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
            'volume_capacity' => 30,
            'volume_iops' => 600,
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
                'title' => 'Validation Error',
                'detail' => 'Specified volume capacity is below the minimum of 50',
                'status' => 422,
                'source' => 'volume_capacity'
            ])->assertResponseStatus(422);
    }

    public function testApplianceSpecVcpuMin()
    {
        factory(ApplianceVersionData::class)->create([
            'key' => 'ukfast.spec.cpu_cores.min',
            'value' => 2,
            'appliance_version_uuid' => $this->applianceVersion->appliance_version_uuid,
        ]);

        $data = [
            'vpc_id' => $this->vpc->id,
            'image_id' => $this->image->id,
            'network_id' => $this->network->id,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
            'volume_capacity' => 30,
            'volume_iops' => 600,
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
                'title' => 'Validation Error',
                'detail' => 'Specified vcpu cores is below the minimum of 2',
                'status' => 422,
                'source' => 'vcpu_cores'
            ])->assertResponseStatus(422);
    }
}
