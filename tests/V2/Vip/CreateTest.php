<?php

namespace Tests\V2\Vip;

use App\Models\V2\IpAddress;
use App\Models\V2\LoadBalancerCluster;
use App\Models\V2\Network;
use App\Models\V2\Vip;
use Tests\TestCase;

class CreateTest extends TestCase
{
    protected $loadbalancer;
    protected $network;

    public function setUp(): void
    {
        parent::setUp();
        $this->loadbalancer = $this->loadbalancer();
        $this->network = $this->network();
    }

    public function testValidDataSucceeds()
    {
        $this->post(
            '/v2/vips',
            [
                'loadbalancer_id' => $this->loadbalancer->id,
                'network_id' => $this->network->id
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )  ->seeInDatabase(
            'vips',
            [
                'loadbalancer_id' => $this->loadbalancer->id,
                'network_id' => $this->network->id
            ],
            'ecloud'
        )
            ->assertResponseStatus(202);
    }
}
