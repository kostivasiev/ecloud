<?php
namespace Tests\V2\Router\EventTests;

use App\Events\V2\NetworkCreated;
use App\Events\V2\RouterCreated;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Network;
use App\Models\V2\Router;
use Faker\Factory as Faker;
use Faker\Generator;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class RouterNetworkCreatedTest extends TestCase
{
    use DatabaseMigrations;

    protected Generator $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }

    public function testRouterWithNetworkIsDispatched()
    {
        Event::fake();

        $router = $this->getRouter();
        $network = $this->getNetwork($router);

        Event::assertDispatched(RouterCreated::class, function ($event) use ($router) {
            return $event->router->id === $router->id;
        });

        Event::assertDispatched(NetworkCreated::class, function ($event) use ($network) {
            return $event->network->id === $network->id;
        });
    }

    public function testRouterWithNetworkNotDispatched()
    {
        Event::fake();

        $router = $this->getRouter();

        Event::assertDispatched(RouterCreated::class, function ($event) use ($router) {
            return $event->router->id === $router->id;
        });

        Event::assertNotDispatched(NetworkCreated::class);

    }

    /**
     * @return AvailabilityZone
     */
    public function getAvailabilityZone(): AvailabilityZone
    {
        return factory(AvailabilityZone::class, 1)
            ->create([
            'id' => 'az-1234abcd',
        ])
            ->first();
    }

    /**
     * @return Router
     */
    public function getRouter(): Router
    {
        return factory(Router::class, 1)
            ->create([
                'id' => 'rtr-1234abcd'
            ])
            ->first();
    }

    /**
     * @param Router|null $router
     * @return Network
     */
    public function getNetwork(?Router $router = null): Network
    {
        $network = factory(Network::class, 1)->create([
            'id'   => 'net-1234abcd',
            'name' => 'net-1234abcd',
        ])
            ->first();
        if (!is_null($router)) {
            $network->router()->associate($router);
            $network->save();
        }
        return $network;
    }
}
