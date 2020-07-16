<?php

namespace Tests\V2\Vpns;

use App\Models\V2\AvailabilityZones;
use App\Models\V2\Routers;
use App\Models\V2\Vpns;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
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
        $vpns = $this->createVpn();
        $data = [
            'router_id'            => $this->createRouters()->id,
            'availability_zone_id' => $this->createAvailabilityZone()->id,
        ];
        $this->patch(
            '/v2/vpns/' . $vpns->getKey(),
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
        $vpns = $this->createVpn();
        $data = [
            'router_id'            => '',
            'availability_zone_id' => $this->createAvailabilityZone()->id,
        ];
        $this->patch(
            '/v2/vpns/' . $vpns->getKey(),
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

    public function testNullAvailabilityZoneIdIsDenied()
    {
        $vpns = $this->createVpn();
        $data = [
            'router_id'            => $this->createRouters()->id,
            'availability_zone_id' => '',
        ];
        $this->patch(
            '/v2/vpns/' . $vpns->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The availability zone id field, when specified, cannot be null',
                'status' => 422,
                'source' => 'availability_zone_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $vpns = $this->createVpn();
        $data = [
            'router_id'            => $this->createRouters()->id,
            'availability_zone_id' => $this->createAvailabilityZone()->id,
        ];
        $this->patch(
            '/v2/vpns/' . $vpns->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(200);

        $vpnItem = Vpns::findOrFail($vpns->getKey());
        $this->assertEquals($data['router_id'], $vpnItem->router_id);
        $this->assertEquals($data['availability_zone_id'], $vpnItem->availability_zone_id);
    }

    /**
     * Create Vpns
     * @return \App\Models\V2\Vpns
     */
    public function createVpn(): Vpns
    {
        $cloud = factory(Vpns::class, 1)->create([
            'router_id'            => $this->createRouters()->id,
            'availability_zone_id' => $this->createAvailabilityZone()->id,
        ])->first();
        $cloud->save();
        $cloud->refresh();
        return $cloud;
    }

    /**
     * Create Availability Zone
     * @return \App\Models\V2\AvailabilityZones
     */
    public function createAvailabilityZone(): AvailabilityZones
    {
        $zone = factory(AvailabilityZones::class, 1)->create()->first();
        $zone->save();
        $zone->refresh();
        return $zone;
    }

    /**
     * Create Router
     * @return \App\Models\V2\Routers
     */
    public function createRouters(): Routers
    {
        $router = factory(Routers::class, 1)->create()->first();
        $router->save();
        $router->refresh();
        return $router;
    }

}