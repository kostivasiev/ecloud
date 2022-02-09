<?php
namespace Tests\V2\IpAddress;

use App\Models\V2\IpAddress;
use App\Models\V2\Network;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    public function setUp():void
    {
        parent::setUp();
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
    }

    public function testValidDataIsSuccessful()
    {
        $ipAddress = IpAddress::factory()->create([
            'network_id' => $this->network()->id
        ]);

        $this->patch(
            '/v2/ip-addresses/' . $ipAddress->id,
            [
                'name' => 'UPDATED',
                'ip_address' => '10.0.0.6',
                'type' => 'cluster',
            ]
        )->seeInDatabase(
            'ip_addresses',
            [
                'name' => 'UPDATED',
                'ip_address' => '10.0.0.6',
                'type' => 'cluster',
                ],
            'ecloud'
        )->assertResponseStatus(200);
    }

    public function testNonAdminCantChangeIpAddress()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write'])));

        $this->patch(
            '/v2/ip-addresses/' . $this->ip()->id,
            [
                'name' => 'UPDATED',
                'ip_address' => '10.0.0.6',
                'type' => 'cluster',
            ]
        )->seeInDatabase(
            'ip_addresses',
            [
                'name' => 'UPDATED',
                'ip_address' => $this->ip()->ip_address,
                'type' => 'cluster',
            ],
            'ecloud'
        )->assertResponseStatus(200);
    }
}
