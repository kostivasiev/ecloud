<?php

namespace Tests\V2\LoadBalancerNetwork;

use App\Events\V2\Task\Created;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CrudTest extends TestCase
{
    use LoadBalancerMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(
            (new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))
                ->setIsAdmin(true)
        );
    }

    public function testIndex()
    {
        $this->loadBalancerNetwork();

        // Assert scope returns resource for [load balancer] owner
        $this->get('/v2/load-balancer-networks')
            ->assertJsonFragment([
                'id' => $this->loadBalancerNetwork()->id,
                'load_balancer_id' => $this->loadBalancer()->id,
                'network_id' => $this->network()->id,
            ])
            ->assertStatus(200);

        // Assert scope does not return resource for non-owner
        $this->be(
            (new Consumer(2, [config('app.name') . '.read', config('app.name') . '.write']))
                ->setIsAdmin(true)
        );
        $this->get('/v2/load-balancer-networks')
            ->assertJsonMissing([
                'id' => $this->loadBalancerNetwork()->id,
            ])
            ->assertStatus(200);

        // Assert scope returns resource for admin
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->get('/v2/load-balancer-networks')
            ->assertJsonFragment([
                'id' => $this->loadBalancerNetwork()->id,
            ])
            ->assertStatus(200);
    }

    public function testShow()
    {
        // Assert scope returns resource for [load balancer] owner
        $this->get('/v2/load-balancer-networks/' . $this->loadBalancerNetwork()->id)
            ->assertJsonFragment([
                'id' => $this->loadBalancerNetwork()->id,
                'name' => $this->loadBalancerNetwork()->id,
                'load_balancer_id' => $this->loadBalancer()->id,
                'network_id' => $this->network()->id,
            ])
            ->assertStatus(200);

        // Assert scope does not return resource for non-owner
        $this->be(
            (new Consumer(2, [config('app.name') . '.read', config('app.name') . '.write']))
                ->setIsAdmin(true)
        );
        $this->get('/v2/load-balancer-networks/' . $this->loadBalancerNetwork()->id)->assertStatus(404);

        // Assert scope returns resource for admin
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->get('/v2/load-balancer-networks/' . $this->loadBalancerNetwork()->id)->assertStatus(200);
    }

    public function testStore()
    {
        Event::fake(Created::class);

        $data = [
            'name' => 'test',
            'load_balancer_id' => $this->loadBalancer()->id,
            'network_id' => $this->network()->id,
        ];

        $this->post('/v2/load-balancer-networks', $data)->assertStatus(202);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testStoreNetworkAlreadyAssignedFails()
    {
        Event::fake(Created::class);

        $this->loadBalancerNetwork();

        $data = [
            'name' => 'test',
            'load_balancer_id' => $this->loadBalancer()->id,
            'network_id' => $this->network()->id,
        ];

        $this->post('/v2/load-balancer-networks', $data)->assertStatus(422);
    }

    public function testUpdate()
    {
        Event::fake(Created::class);

        $data = [
            'name' => 'Test - UPDATED',
        ];

        $this->patch('/v2/load-balancer-networks/' . $this->loadBalancerNetwork()->id, $data)->assertStatus(202);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testDestroy()
    {
        Event::fake(Created::class);

        $this->delete('/v2/load-balancer-networks/' . $this->loadBalancerNetwork()->id)->assertStatus(202);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'sync_delete';
        });
    }
}
