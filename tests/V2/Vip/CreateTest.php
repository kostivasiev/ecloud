<?php

namespace Tests\V2\Vip;

use App\Events\V2\Task\Created;
use App\Models\V2\Vip;
use App\Support\Sync;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    use LoadBalancerMock, VipMock;

    public function testValidDataSucceeds()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $this->loadBalancerNetwork();

        Event::fake(Created::class);

        $this->post(
            '/v2/vips',
            [
                'load_balancer_id' => $this->loadBalancer()->id,
            ]
        )->assertStatus(202);
        $this->assertDatabaseHas(
            'vips',
            [
                'load_balancer_network_id' => ($this->loadBalancer()->loadBalancerNetworks->first())->id,
            ],
            'ecloud'
        );

        Event::assertDispatched(Created::class, function ($event) {
            $this->assertFalse($event->model->data['allocate_floating_ip']);
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        });
    }

    public function testValidDataWithFipSucceeds()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $this->loadBalancerNetwork();

        Event::fake(Created::class);

        $this->post(
            '/v2/vips',
            [
                'load_balancer_id' => $this->loadBalancer()->id,
                'allocate_floating_ip' => true
            ]
        )->assertStatus(202);
        $this->assertDatabaseHas(
            'vips',
            [
                'load_balancer_network_id' => ($this->loadBalancer()->loadBalancerNetworks->first())->id,
            ],
            'ecloud'
        );

        Event::assertDispatched(Created::class, function ($event) {
            $this->assertTrue($event->model->data['allocate_floating_ip']);
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        });
    }
}
