<?php

namespace Tests\V2\Vip;

use App\Models\V2\IpAddress;
use App\Models\V2\Vip;
use Tests\TestCase;

class CreateTest extends TestCase
{
    protected $ip;

    public function setUp(): void
    {
        parent::setUp();
        $this->ip = IpAddress::factory()->create();
        Vip::factory()->create();
    }

    public function testValidDataSucceeds()
    {
        $this->post(
            '/v2/vips',
            [
                'ip_address_id' => $this->ip->id
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )  ->seeInDatabase(
            'vips',
            [
                'ip_address_id' => $this->ip->id
            ],
            'ecloud'
        )
            ->assertResponseStatus(202);
    }
}
