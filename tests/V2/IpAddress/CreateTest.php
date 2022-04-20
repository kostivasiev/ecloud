<?php
namespace Tests\V2\IpAddress;

use App\Models\V2\IpAddress;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    public function setUp():void
    {
        parent::setUp();
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'name' => 'Test',
            'ip_address' => '10.0.0.4',
            'network_id' => $this->network()->id,
            'type' => IpAddress::TYPE_DHCP,
        ];

        $this->post('/v2/ip-addresses', $data)
            ->assertStatus(201);
        $this->assertDatabaseHas(
            'ip_addresses',
            $data,
            'ecloud'
        );
    }

    public function testSuccessNotAdmin()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(false));

        $data = [
            'name' => 'Test',
            'ip_address' => '10.0.0.4',
            'network_id' => $this->network()->id,
            'type' => IpAddress::TYPE_DHCP,
        ];

        $this->post('/v2/ip-addresses', $data)
            ->assertStatus(201);
        $this->assertDatabaseHas(
            'ip_addresses',
            $data,
            'ecloud'
        );
    }

    public function testIpAddressNotInSubnetFails()
    {
        $data = [
            'name' => 'Test',
            'ip_address' => '1.1.1.1',
            'network_id' => $this->network()->id,
            'type' => IpAddress::TYPE_DHCP,
        ];

        $this->post('/v2/ip-addresses', $data)->assertStatus(422);
    }
}