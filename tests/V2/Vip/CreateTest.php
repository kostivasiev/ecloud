<?php

namespace Tests\V2\Vip;

use App\Models\V2\IpAddress;
use App\Models\V2\LoadBalancerCluster;
use App\Models\V2\Network;
use App\Models\V2\Vip;
use Tests\TestCase;

class CreateTest extends TestCase
{

    public function testValidDataSucceeds()
    {
        $this->post(
            '/v2/vips',
            [
                'load_balancer_id' => $this->loadBalancer()->id,
                'network_id' => $this->network()->id
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )  ->seeInDatabase(
            'vips',
            [
                'load_balancer_id' => $this->loadBalancer()->id,
                'network_id' => $this->network()->id
            ],
            'ecloud'
        )
            ->assertResponseStatus(202);
    }
}
