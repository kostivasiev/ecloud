<?php

namespace Tests\V2\Vip;

use App\Models\V2\IpAddress;
use App\Models\V2\Vip;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    protected $ip;
    protected $vip;

    public function setUp(): void
    {
        parent::setUp();
        $this->ip = IpAddress::factory()->create();
        $this->vip = Vip::factory()->create();
    }

    public function testValidDataIsSuccessful()
    {
        $data = [
            "ip_address_id" => $this->ip->id,
        ];

        $this->patch('/v2/vips/' . $this->vip->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read,ecloud.write',
            ]
        )->seeInDatabase(
            'vips',
            $data,
            'ecloud'
        )->assertResponseStatus(202);
    }
}
