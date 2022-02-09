<?php

namespace Tests\V2\Vip;

use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    use VipMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testGetItemCollection()
    {
        $this->vip();

        $this->get('/v2/vips')
            ->seeJson([
                'id' => $this->vip()->id,
                'load_balancer_network_id' => $this->loadBalancerNetwork()->id,
        ])->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/vips/' . $this->vip()->id)->seeJson([
            'id' => $this->vip()->id,
            'load_balancer_network_id' => $this->loadBalancerNetwork()->id,
        ])->assertResponseStatus(200);
    }
}
