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
            'ip_address' => '1.1.1.1',
            'type' => 'normal',
        ];

        $this->post('/v2/ip-addresses', $data)
            ->seeInDatabase(
                'ip_addresses',
                $data,
                'ecloud'
            )->assertResponseStatus(201);
    }

    public function testNotAdminUnauthorised()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(false));
        $this->post('/v2/ip-addresses', [
            'name' => 'Test',
            'ip_address' => '1.1.1.1',
            'type' => 'normal',
        ])->assertResponseStatus(401);
    }
}