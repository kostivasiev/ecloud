<?php

namespace Tests\V2\Vpn;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Router;
use App\Models\V2\Vpn;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{

    use DatabaseMigrations;

    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }

    public function testNoPermsIsDenied()
    {
        $data = [
            'router_id'            => $this->createRouters()->id,
            'availability_zone_id' => $this->createAvailabilityZone()->id,
        ];
        $this->post(
            '/v2/vpns',
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

    public function testNullRouterIdIsFailed()
    {
        $data = [
            'availability_zone_id' => $this->createAvailabilityZone()->id,
        ];
        $this->post(
            '/v2/vpns',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The router id field is required',
                'status' => 422,
                'source' => 'router_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testNullAvailabilityZoneIdIsFailed()
    {
        $data = [
            'router_id' => $this->createRouters()->id,
        ];
        $this->post(
            '/v2/vpns',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The availability zone id field is required',
                'status' => 422,
                'source' => 'availability_zone_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        $router = $this->createRouters();
        $zone = $this->createAvailabilityZone();
        $data = [
            'router_id'            => $router->id,
            'availability_zone_id' => $zone->id,
        ];
        $this->post(
            '/v2/vpns',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);

        $vpnId = (json_decode($this->response->getContent()))->data->id;
        $vpnItem = Vpn::findOrFail($vpnId);
        $this->assertEquals($vpnItem->router_id, $router->id);
        $this->assertEquals($vpnItem->availability_zone_id, $zone->id);
    }

    /**
     * Create Availability Zone
     * @return \App\Models\V2\AvailabilityZone
     */
    public function createAvailabilityZone(): AvailabilityZone
    {
        $zone = factory(AvailabilityZone::class, 1)->create()->first();
        $zone->save();
        $zone->refresh();
        return $zone;
    }

    /**
     * Create Router
     * @return \App\Models\V2\Router
     */
    public function createRouters(): Router
    {
        $router = factory(Router::class, 1)->create()->first();
        $router->save();
        $router->refresh();
        return $router;
    }

}