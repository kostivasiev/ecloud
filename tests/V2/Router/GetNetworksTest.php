<?php

namespace Tests\V2\Router;

use Tests\TestCase;

class GetNetworksTest extends TestCase
{
    public function testGetCollection()
    {
        $this->network();
        $this->get(
            '/v2/routers/'.$this->router()->id.'/networks',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->assertJsonFragment([
                'id'        => $this->network()->id,
                'name'      => $this->network()->name,
                'router_id' => $this->network()->router_id,
            ])
            ->assertStatus(200);
    }
}
