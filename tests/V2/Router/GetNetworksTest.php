<?php

namespace Tests\V2\Router;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetNetworksTest extends TestCase
{
    use DatabaseMigrations;

    public function testGetCollection()
    {
        $this->network();
        $this->get(
            '/v2/routers/'.$this->router()->getKey().'/networks',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'        => $this->network()->getKey(),
                'name'      => $this->network()->name,
                'router_id' => $this->network()->router_id,
            ])
            ->assertResponseStatus(200);
    }
}
