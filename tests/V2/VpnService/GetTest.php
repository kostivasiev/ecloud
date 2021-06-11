<?php

namespace Tests\V2\VpnService;

use App\Models\V2\VpnService;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    protected $vpn;

    public function setUp(): void
    {
        parent::setUp();

        $this->vpn = factory(VpnService::class)->create([
            'name' => 'Unit Test VPN',
            'router_id' => $this->router()->id,
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/vpn-services',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->vpn->id,
                'router_id' => $this->vpn->router_id,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get(
            '/v2/vpn-services/' . $this->vpn->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->vpn->id,
                'router_id' => $this->vpn->router_id,
            ])
            ->assertResponseStatus(200);
    }
}
