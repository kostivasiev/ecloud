<?php

namespace Tests\V2\Network;

use App\Events\V2\NetworkCreated;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Network;
use App\Models\V2\Region;
use Illuminate\Support\Facades\Event;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    /** @var Region */
    private $region;

    /** @var Vpc */
    private $vpc;

    protected $router;
    protected $availability_zone;

    public function setUp(): void
    {
        parent::setUp();

        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'code'               => 'TIM1',
            'name'               => 'Tims Region 1',
            'datacentre_site_id' => 1,
            'region_id'          => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->router = factory(Router::class)->create([
            'name' => 'Manchester Router 1',
            'vpc_id' => $this->vpc->getKey()
        ]);
    }

    public function testNoPermsIsDenied()
    {
        $data = [
            'name'    => 'Manchester Network',
        ];
        $this->post(
            '/v2/networks',
            $data,
            []
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testInvalidRouterIdIsFailed()
    {
        $data = [
            'name' => 'Manchester Network',
            'router_id' => 'x',
        ];

        $this->post(
            '/v2/networks',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The specified router id was not found',
                'status' => 422,
                'source' => 'router_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testNotOwnedRouterIdIsFailed()
    {
        $data = [
            'name' => 'Manchester Network',
            'router_id' => 'x',
        ];

        $this->post(
            '/v2/networks',
            $data,
            [
                'X-consumer-custom-id' => '2-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The specified router id was not found',
                'status' => 422,
                'source' => 'router_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'name' => 'Manchester Network',
            'router_id' => $this->router->getKey()
        ];

        $this->post(
            '/v2/networks',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(201);
    }

    public function testCreateDispatchesEvent()
    {
        Event::fake();

        $network = factory(Network::class)->create([
            'id' => 'net-abc123',
            'router_id' => 'x',
        ]);

        Event::assertDispatched(NetworkCreated::class, function ($event) use ($network) {
            return $event->network->id === $network->id;
        });
    }
}
