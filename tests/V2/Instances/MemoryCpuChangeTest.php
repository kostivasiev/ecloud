<?php
namespace Tests\V2\Instances;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\ServerLicense;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Admin\Devices\AdminClient;

class MemoryCpuChangeTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $appliance;
    protected $applianceVersion;
    protected $instance;
    protected $region;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->appliance = (factory(Appliance::class)->create([
            'appliance_name' => 'Test Appliance',
        ]))->refresh();
        $this->applianceVersion = (factory(ApplianceVersion::class)->create([
            'appliance_version_appliance_id' => $this->appliance->getKey(),
            'appliance_version_server_license_id' => 2,
        ]))->refresh();
        $this->region = factory(Region::class)->create();
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->instance = factory(Instance::class)->create([
            'appliance_version_id' => $this->applianceVersion->appliance_version_uuid,
            'vpc_id' => $this->vpc->getKey(),
        ]);
        $mockAdminDevices = \Mockery::mock(AdminClient::class)
            ->shouldAllowMockingProtectedMethods();
        app()->bind(AdminClient::class, function () use ($mockAdminDevices) {
            $mockedResponse = new \stdClass();
            $mockedResponse->category = "Linux";
            $mockAdminDevices->shouldReceive('licenses->getById')->andReturn($mockedResponse);
            return $mockAdminDevices;
        });
    }

    public function testServerLicense()
    {
        dd(
            $this->instance->applianceVersion->serverLicense()->category,
            $this->instance->platform
        );
        $this->assertTrue(true);
    }
}
