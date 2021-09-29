<?php

namespace Tests\V2\Nic;

use App\Models\V2\IpAddress;
use App\Models\V2\Nic;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
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
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
    }

    public function testGetCollection()
    {
        $this->get('/v2/nics')
            ->seeJson([
            'mac_address' => $this->mac_address,
            'instance_id' => $this->instance()->id,
            'network_id' => $this->network()->id,
            'ip_address' => $this->ip_address,
        ])->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get('/v2/nics/' . $this->nic->id)
            ->seeJson([
            'mac_address' => $this->mac_address,
            'instance_id' => $this->instance()->id,
            'network_id' => $this->network()->id,
            'ip_address' => $this->ip_address,
        ])->assertResponseStatus(200);
    }

    public function testGetIpAddressCollection()
    {
        $ipAddress = IpAddress::factory()->create();

        $this->nic->ipAddresses()->sync($ipAddress);

        $this->get('/v2/nics/' . $this->nic->id . '/ip-addresses')
            ->seeJson(
                [
                    'id' => $ipAddress->id,
                ]
            )->assertResponseStatus(200);
    }
}
