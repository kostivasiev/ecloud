<?php

namespace Tests\V2\Nic;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $availability_zone;
    protected $instance;
    protected $mac_address;
    protected $ip_address;
    protected $network;
    protected $nic;
    protected $region;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->ip_address = $this->faker->ipv4;
        $this->mac_address = $this->faker->macAddress;
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->id
        ]);
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->id,
        ]);
        $this->network = factory(Network::class)->create([
            'name' => 'Manchester Network',
        ]);
        $this->nic = factory(Nic::class)->create([
            'mac_address' => $this->mac_address,
            'instance_id' => $this->instance->id,
            'network_id' => $this->network->id,
            'ip_address' => $this->ip_address,
        ]);
    }

    public function testGetCollection()
    {
        $this->get('/v2/nics', [
            'X-consumer-custom_id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'mac_address' => $this->mac_address,
            'instance_id' => $this->instance->id,
            'network_id' => $this->network->id,
            'ip_address' => $this->ip_address,
        ])->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get('/v2/nics/' . $this->nic->id, [
            'X-consumer-custom_id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'mac_address' => $this->mac_address,
            'instance_id' => $this->instance->id,
            'network_id' => $this->network->id,
            'ip_address' => $this->ip_address,
        ])->assertResponseStatus(200);
    }
}
