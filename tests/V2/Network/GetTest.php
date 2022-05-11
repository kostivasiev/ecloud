<?php

namespace Tests\V2\Network;

use Tests\TestCase;

class GetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->network();
    }

    public function testNoPermsIsDenied()
    {
        $this->get(
            '/v2/networks',
            []
        )
            ->assertJsonFragment([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])
            ->assertStatus(401);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/networks',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->assertJsonFragment([
                'id' => $this->network()->id,
                'name' => $this->network()->name,
                'subnet' => '10.0.0.0/24'
            ])
            ->assertStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get(
            '/v2/networks/' . $this->network()->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->assertJsonFragment([
                'id' => $this->network()->id,
                'name' => $this->network()->name,
                'subnet' => '10.0.0.0/24'
            ])
            ->assertStatus(200);
    }

}
