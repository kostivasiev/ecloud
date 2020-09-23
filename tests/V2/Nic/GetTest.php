<?php

namespace Tests\V2\Nic;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $availability_zone;
    protected $instance;
    protected $macAddress;
    protected $network;
    protected $nic;
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
        $this->nic = factory(Nic::class)->create([
            'mac_address' => $this->macAddress,
            'instance_id' => $this->instance->getKey(),
            'network_id'  => $this->network->getKey(),
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/nics',
            [
                'X-consumer-custom_id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'mac_address' => $this->macAddress,
                'instance_id' => $this->instance->getkey(),
                'network_id'  => $this->network->getKey(),
            ])
            ->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/nics/' . $this->nic->getKey(),
            [
                'X-consumer-custom_id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'mac_address' => $this->macAddress,
                'instance_id' => $this->instance->getkey(),
                'network_id'  => $this->network->getKey(),
            ])
            ->assertResponseStatus(200);
    }
}
