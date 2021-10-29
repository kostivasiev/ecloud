<?php

namespace Tests\V2\Vip;

use App\Events\V2\Task\Created;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    use LoadBalancerMock;

    public function testValidDataSucceeds()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        Event::fake(Created::class);

        $this->post(
            '/v2/vips',
            [
                'load_balancer_id' => $this->loadBalancer()->id,
                'network_id' => $this->network()->id,
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

        Event::assertDispatched(Created::class, function ($event) {
            $this->assertFalse($event->model->data['allocate_floating_ip']);
            return $event->model->name == 'sync_update';
        });
    }

    public function testValidDataWithFipSucceeds()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        Event::fake(Created::class);

        $this->post(
            '/v2/vips',
            [
                'load_balancer_id' => $this->loadBalancer()->id,
                'network_id' => $this->network()->id,
                'allocate_floating_ip' => true
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

        Event::assertDispatched(Created::class, function ($event) {
            $this->assertTrue($event->model->data['allocate_floating_ip']);
            return $event->model->name == 'sync_update';
        });
    }
}
