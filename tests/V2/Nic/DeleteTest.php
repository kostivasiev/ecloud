<?php

namespace Tests\V2\Nic;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $availabilityZone;
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
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id
        ]);

        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->id
        ]);
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->id,
            'availability_zone_id' => $this->availabilityZone->id
        ]);
        $this->network = factory(Network::class)->create([
            'name' => 'Manchester Network',
        ]);
        $this->nic = factory(Nic::class)->create([
            'mac_address' => $this->macAddress,
            'instance_id' => $this->instance->id,
            'network_id' => $this->network->id,
        ])->refresh();
    }

    public function testValidNicSucceeds()
    {
        $this->delete('/v2/nics/' . $this->nic->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(204);
    }
}
