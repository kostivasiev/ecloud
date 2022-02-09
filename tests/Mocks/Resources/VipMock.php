<?php

namespace Tests\Mocks\Resources;

use App\Models\V2\Vip;
use Illuminate\Database\Eloquent\Model;

trait VipMock
{
    use LoadBalancerMock;

    private $vip;

    public function vip($id = 'vip-aaaaaaaa-dev'): Vip
    {
        if (!$this->vip) {
            Model::withoutEvents(function() use ($id) {
                $this->vip = Vip::factory()->create([
                    'id' => $id,
                    'name' => $id,
                    'load_balancer_network_id' => $this->loadBalancerNetwork()->id,
                ]);
            });
        }
        return $this->vip;
    }
}