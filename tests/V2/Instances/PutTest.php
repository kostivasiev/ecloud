<?php

namespace Tests\V2\Instances;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class PutTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $availability_zone;
    protected $instance;
    protected $region;
    protected $vpc;

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
        $this->instance = factory(Instance::class)->create([
            'vpc_id'               => $this->vpc->getKey(),
            'name'                 => 'GetTest Default',
            'appliance_version_id' => $this->appliance_version->uuid,
            'vcpu_cores'           => 1,
            'ram_capacity'         => 1024,
        ]);
    }

    public function testPowerOn()
    {
        $this->put(
            '/v2/instances/' . $this->instance->getKey() . '/power-on',
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(202);
    }

    public function testPowerOff()
    {
        $this->put(
            '/v2/instances/' . $this->instance->getKey() . '/power-off',
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(202);
    }

}