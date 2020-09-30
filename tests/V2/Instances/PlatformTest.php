<?php

namespace Tests\V2\Instances;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\Instance;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Admin\Devices\AdminClient;

class PlatformTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $availability_zone;
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
        Vpc::flushEventListeners();
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->appliance = factory(Appliance::class)->create([
            'appliance_name' => 'Test Appliance',
        ])->refresh();
        $this->appliance_version = factory(ApplianceVersion::class)->create([
            'appliance_version_appliance_id' => $this->appliance->appliance_id,
        ])->refresh();
        $mockAdminDevices = \Mockery::mock(AdminClient::class)
            ->shouldAllowMockingProtectedMethods();
        app()->bind(AdminClient::class, function () use ($mockAdminDevices) {
            $mockedResponse = new \stdClass();
            $mockedResponse->category = "Linux";
            $mockAdminDevices->shouldReceive('licenses->getById')->andReturn($mockedResponse);
            return $mockAdminDevices;
        });
    }

    public function testSettingPlatform()
    {
        $data = [
            'vpc_id' => $this->vpc->getKey(),
            'appliance_id' => $this->appliance->uuid,
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
        // Check that the platform id has been populated
        $this->assertEquals('Linux', $instance->platform);
    }
}