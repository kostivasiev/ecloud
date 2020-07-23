<?php

namespace Tests\V2\Vpn;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Models\V2\Vpn;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->vpc = factory(Vpc::class, 1)->create([
            'name'    => 'Manchester DC',
        ])->first();
    }

    public function testNoPermsIsDenied()
    {
        $this->get(
            '/v2/vpns',
            []
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testGetCollection()
    {
        $vpns = $this->createVpn();
        $this->get(
            '/v2/vpns',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'                   => $vpns->id,
                'router_id'            => $vpns->router_id,
                'availability_zone_id' => $vpns->availability_zone_id,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $vpns = $this->createVpn();
        $this->get(
            '/v2/vpns/' . $vpns->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'                   => $vpns->id,
                'router_id'            => $vpns->router_id,
                'availability_zone_id' => $vpns->availability_zone_id,
            ])
            ->assertResponseStatus(200);
    }

    /**
     * Create Vpns
     * @return \App\Models\V2\Vpn
     */
    public function createVpn(): Vpn
    {
        $cloud = factory(Vpn::class, 1)->create([
            'router_id'            => $this->createRouters()->id,
            'availability_zone_id' => $this->createAvailabilityZone()->id,
        ])->first();
        $cloud->save();
        $cloud->refresh();
        return $cloud;
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
        $router = factory(Router::class, 1)->create([
            'vpc_id' => $this->vpc->getKey()
        ])->first();
        $router->save();
        $router->refresh();
        return $router;
    }

}
