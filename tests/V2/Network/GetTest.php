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
            ->seeJson([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
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
            ->seeJson([
                'id' => $this->network()->id,
                'name' => $this->network()->name,
                'subnet' => '10.0.0.0/24'
            ])
            ->assertResponseStatus(200);
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
            ->seeJson([
                'id' => $this->network()->id,
                'name' => $this->network()->name,
                'subnet' => '10.0.0.0/24'
            ])
            ->assertResponseStatus(200);
    }

}
