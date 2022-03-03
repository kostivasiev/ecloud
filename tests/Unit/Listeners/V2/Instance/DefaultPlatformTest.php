<?php

namespace Tests\Unit\Listeners\V2\Instance;

use App\Listeners\V2\Instance\DefaultPlatform;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

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
            $this->image = Image::factory()->create([
                'id' => 'img-test',
            ]);
            $this->instance = Instance::factory()->create([
                'id' => 'i-test',
            ]);
            $this->instance->image()->associate($this->image);
        });

        $this->network = Network::factory()->create();
    }

    public function testSettingPlatform()
    {
        $listener = new DefaultPlatform();
        $listener->handle(new \App\Events\V2\Instance\Creating($this->instance));

        // Check that the platform id has been populated
        $this->assertEquals('Linux', $this->instance->platform);
    }
}
