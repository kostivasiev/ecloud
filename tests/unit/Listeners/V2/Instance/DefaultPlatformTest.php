<?php
namespace Tests\unit\Listeners\V2\Instance;

use App\Listeners\V2\Instance\ComputeChange;
use App\Listeners\V2\Instance\DefaultPlatform;
use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\BillingMetric;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Region;
use App\Models\V2\Sync;
use App\Models\V2\Vpc;
use App\Providers\EncryptionServiceProvider;
use App\Services\V2\KingpinService;
use Faker\Factory as Faker;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Admin\Devices\AdminClient;

class DefaultPlatformTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $availability_zone;
    protected $network;
    protected $region;
    protected $vpc;
    protected $appliance;
    protected $appliance_version;
    protected $image;
    protected $instance;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        Model::withoutEvents(function() {
            $this->appliance = factory(Appliance::class)->create([
                'appliance_uuid' => 'aa085bfc-bbe9-4825-b636-1f221d6c3fa9',
                'appliance_name' => 'Test Appliance',
            ]);
            $this->appliance_version = factory(ApplianceVersion::class)->create([
                'appliance_version_uuid' => 'd7c4a253-0718-4ef7-adb2-ad348ae96371',
                'appliance_version_appliance_id' => $this->appliance->id,
            ]);
            $this->image = factory(Image::class)->create([
                'id' => 'img-test',
                'appliance_version_id' => $this->appliance_version->id,
            ]);
            $this->instance = factory(Instance::class)->create([
                'id' => 'i-test',
            ]);
            $this->instance->image()->associate($this->image);
        });

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

    public function testSettingPlatform()
    {
        $listener = new DefaultPlatform();
        $listener->handle(new \App\Events\V2\Instance\Creating($this->instance));

        // Check that the platform id has been populated
        $this->assertEquals('Linux', $this->instance->platform);
    }
}
