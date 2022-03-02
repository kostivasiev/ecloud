<?php

namespace Tests\V2\Nic;

use App\Models\V2\IpAddress;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    protected \Faker\Generator $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
    }

    public function testGetCollection()
    {
        $this->nic();
        $this->get('/v2/nics')
            ->seeJson([
            'mac_address' => $this->nic()->mac_address,
            'instance_id' => $this->instanceModel()->id,
            'network_id' => $this->network()->id,
        ])->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get('/v2/nics/' . $this->nic()->id)
            ->seeJson([
            'mac_address' => $this->nic()->mac_address,
            'instance_id' => $this->instanceModel()->id,
            'network_id' => $this->network()->id,
        ])->assertResponseStatus(200);
    }

    public function testGetIpAddressCollection()
    {
        $ipAddress = IpAddress::factory()->create();

        $this->nic()->ipAddresses()->sync($ipAddress);

        $this->get('/v2/nics/' . $this->nic()->id . '/ip-addresses')
            ->seeJson(
                [
                    'id' => $ipAddress->id,
                ]
            )->assertResponseStatus(200);
    }
}
