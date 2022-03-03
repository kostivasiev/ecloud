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

        // Create a VIP and assign a cluster IP to it.
        $this->vip()->assignClusterIp();
        $this->vip->setAttribute('config_id', 12345)->saveQuietly();
    }

    public function testGetItemCollection()
    {
        $this->get('/v2/vips')
            ->assertJsonFragment([
                'id' => $this->vip()->id,
                'load_balancer_id' => $this->loadBalancer()->id,
                'ip_address_id' => $this->vip()->ipAddress->id,
                'config_id' => 12345,
            ])->assertStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/vips/' . $this->vip()->id)
            ->assertJsonFragment([
                'id' => $this->vip()->id,
                'load_balancer_id' => $this->loadBalancer()->id,
                'ip_address_id' => $this->vip()->ipAddress->id,
                'config_id' => 12345,
            ])->assertStatus(200);
    }
}
