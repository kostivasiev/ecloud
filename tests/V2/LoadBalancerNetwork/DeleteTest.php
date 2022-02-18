<?php

namespace Tests\V2\LoadBalancerNetwork;

use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    use LoadBalancerMock, VipMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(
            (new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))
                ->setIsAdmin(true)
        );
    }

    public function testVipAssignedFails()
    {
        $this->vip();

        $this->delete('/v2/load-balancer-networks/' . $this->loadBalancerNetwork()->id)
            ->assertResponseStatus(412);
    }
}
