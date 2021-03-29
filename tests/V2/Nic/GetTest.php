<?php

namespace Tests\V2\Nic;

use App\Models\V2\Nic;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $mac_address;
    protected $ip_address;
    protected $nic;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->ip_address = $this->faker->ipv4;
        $this->mac_address = $this->faker->macAddress;

        Nic::withoutEvents(function() {
            $this->nic = factory(Nic::class)->create([
                'id' => 'nic-test',
                'mac_address' => $this->mac_address,
                'instance_id' => $this->instance()->id,
                'network_id' => $this->network()->id,
                'ip_address' => $this->ip_address,
            ]);
        });
    }

    public function testGetCollection()
    {
        $this->get('/v2/nics', [
            'X-consumer-custom_id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'mac_address' => $this->mac_address,
            'instance_id' => $this->instance()->id,
            'network_id' => $this->network()->id,
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
            'instance_id' => $this->instance()->id,
            'network_id' => $this->network()->id,
            'ip_address' => $this->ip_address,
        ])->assertResponseStatus(200);
    }
}
