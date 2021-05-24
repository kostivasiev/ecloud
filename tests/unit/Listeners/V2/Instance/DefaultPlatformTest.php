<?php
namespace Tests\unit\Listeners\V2\Instance;

use App\Listeners\V2\Instance\DefaultPlatform;
use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Admin\Devices\AdminClient;

class DefaultPlatformTest extends TestCase
{
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
            $this->image = factory(Image::class)->create([
                'id' => 'img-test',
            ]);
            $this->instance = factory(Instance::class)->create([
                'id' => 'i-test',
            ]);
            $this->instance->image()->associate($this->image);
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
