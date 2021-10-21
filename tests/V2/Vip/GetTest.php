<?php

namespace Tests\V2\Vip;

use App\Models\V2\IpAddress;
use App\Models\V2\Vip;
use Tests\TestCase;

class GetTest extends TestCase
{
    protected $vip;

    public function setUp(): void
    {
        parent::setUp();
        $this->vip = $this->vip();
    }

    public function testGetItemCollection()
    {

        $this->get('/v2/vips', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->vip->id,
            'loadbalancer_id' => $this->vip->loadbalancer_id,
            'network_id' => $this->vip->network_id
        ])->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/vips/' . $this->vip->id, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->vip->id,
            'loadbalancer_id' => $this->vip->loadbalancer_id,
            'network_id' => $this->vip->network_id
        ])->assertResponseStatus(200);
    }
}
