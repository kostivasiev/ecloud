<?php

namespace Tests\V2\Nic;

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
    protected $macAddress;
    protected $network;
    protected $region;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->macAddress = $this->faker->macAddress;
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        Vpc::flushEventListeners();
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ]);
        $this->network = factory(Network::class)->create([
            'name' => 'Manchester Network',
        ]);
    }

    public function testValidDataSucceeds()
    {
        $this->post(
            '/v2/nics',
            [
                'mac_address' => $this->faker->macAddress,
                'instance_id' => $this->instance->getKey(),
                'network_id'  => $this->network->getKey(),
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);
    }
}