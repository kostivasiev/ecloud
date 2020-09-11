<?php

namespace Tests\V2\Vpn;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Models\V2\Vpn;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    /** @var Region */
    private $region;

    /** @var Vpc */
    private $vpc;

    /** @var AvailabilityZone */
    private $availabilityZone;

    /** @var Router */
    private $router;

    /** @var Vpn */
    private $vpn;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);
        $this->vpn = factory(Vpn::class)->create([
            'router_id' => $this->router->id,
        ]);
    }

    public function testNoPermsIsDenied()
    {
        $router = factory(Router::class)->create();

        $vpn = factory(Vpn::class)->create([
            'router_id' => $router->id,
        ]);
        $data = [
            'router_id' => $router->id,
        ];
        $this->patch(
            '/v2/vpns/' . $vpn->getKey(),
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

    public function testNullRouterIdIsDenied()
    {
        $router = factory(Router::class)->create();
        $vpn = factory(Vpn::class)->create([
            'router_id' => $router->id,
        ]);
        $data = [
            'router_id' => '',
        ];
        $this->patch(
            '/v2/vpns/' . $vpn->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The router id field, when specified, cannot be null',
                'status' => 422,
                'source' => 'router_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testNotOwnedRouterResourceIsFailed()
    {
        $this->vpc->reseller_id = 3;
        $this->vpc->save();
        $this->patch('/v2/vpns/' . $this->vpn->getKey(), [
            'router_id' => $this->router->getKey(),
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title'  => 'Validation Error',
            'detail' => 'The specified router id was not found',
            'status' => 422,
            'source' => 'router_id'
        ])->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $router = factory(Router::class)->create();
        $vpn = factory(Vpn::class)->create([
            'router_id' => $router->id,
        ]);
        $data = [
            'router_id' => $router->id,
        ];
        $this->patch(
            '/v2/vpns/' . $vpn->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(200);

        $vpnItem = Vpn::findOrFail($vpn->getKey());
        $this->assertEquals($data['router_id'], $vpnItem->router_id);
    }
}
