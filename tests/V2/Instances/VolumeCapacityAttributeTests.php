<?php

namespace Tests\V2\Instances;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use App\Services\V2\KingpinService;
use Faker\Factory as Faker;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class VolumeCapacityAttributeTests extends TestCase
{
    protected \Faker\Generator $faker;
    protected $vpc;
    protected $instance;
    protected $volumes;

    public function setUp(): void
    {
        parent::setUp();

        $region = factory(Region::class)->create();
        $availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $region->id,
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'name' => 'Manchester VPC',
        ]);
        $this->instance = Instance::factory()->create([
            'vpc_id' => $this->vpc->id,
            'availability_zone_id' => $availabilityZone->id,
        ]);

        $this->volumes = factory(Volume::class, 2)->create([
            'vpc_id' => $this->vpc->id,
            'capacity' => 10,
        ]);
    }

    /**
     * Test volume capacity is as expected in collection when a single volume is connected to an instance
     */
    public function testGetInstanceCollection_SingleVolume_ExpectedVolumeCapacityInCollection()
    {
        $this->volumes->first()->instances()->attach($this->instance);
        $this->get(
            '/v2/instances',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'volume_capacity' => 10,
            ])
            ->assertResponseStatus(200);
    }

    /**
     * Test volume capacity attribute is as expected in collection when multiple volumes are connected to an instance
     */
    public function testGetInstanceCollection_MultipleVolumes_ExpectedVolumeCapacityInCollection()
    {
        $this->volumes->get(0)->instances()->attach($this->instance);
        $this->volumes->get(1)->instances()->attach($this->instance);
        $this->get(
            '/v2/instances',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'volume_capacity' => 20,
            ])
            ->assertResponseStatus(200);
    }

    /**
     * Test volume capacity attribute is as expected in item when a single volume is connected to an instance
     */
    public function testGetInstance_SingleVolume_ExpectedVolumeCapacityInItem()
    {
        $this->volumes->first()->instances()->attach($this->instance);
        $this->get(
            '/v2/instances/' . $this->instance->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'volume_capacity' => 10,
            ])
            ->assertResponseStatus(200);
    }
}
