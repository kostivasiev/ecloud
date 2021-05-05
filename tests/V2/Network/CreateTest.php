<?php

namespace Tests\V2\Network;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Network;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    protected $region;
    protected $vpc;
    protected $router;
    protected $availabilityZone;

    public function setUp(): void
    {
        parent::setUp();

        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id
        ]);
    }

    public function testValidDataSucceeds()
    {
        $this->post(
            '/v2/networks',
            [
                'name' => 'Manchester Network',
                'router_id' => $this->router->id,
                'subnet' => '10.0.0.0/24'
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )  ->seeInDatabase(
            'networks',
            [
                'name' => 'Manchester Network',
                'router_id' => $this->router->id,
                'subnet' => '10.0.0.0/24'
            ],
            'ecloud'
        )
            ->assertResponseStatus(202);
    }

    public function testCreateDispatchesEvent()
    {
        Event::fake();

        $network = factory(Network::class)->create([
            'id' => 'net-abc123',
            'router_id' => 'x',
        ]);

        Event::assertDispatched(\App\Events\V2\Network\Created::class, function ($event) use ($network) {
            return $event->model->id === $network->id;
        });
    }
}
