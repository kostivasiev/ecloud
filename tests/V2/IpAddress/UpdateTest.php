<?php
namespace Tests\V2\IpAddress;

use App\Models\V2\IpAddress;
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
        $ipAddress = IpAddress::factory()->create();

        $this->patch(
            '/v2/ip-addresses/' . $ipAddress->id, [
                'name' => 'UPDATED',
                'ip_address' => '2.2.2.2',
                'type' => 'cluster',
            ])
            ->seeInDatabase('ip_addresses', [
                'name' => 'UPDATED',
                'ip_address' => '2.2.2.2',
                'type' => 'cluster',
            ],
            'ecloud')
            ->assertResponseStatus(200);
    }
}