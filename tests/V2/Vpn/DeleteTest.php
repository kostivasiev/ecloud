<?php

namespace Tests\V2\Vpn;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Router;
use App\Models\V2\Vpn;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
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
        $this->delete(
            '/v2/vpns/' . $vpns->getKey(),
            [],
            []
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testFailInvalidId()
    {
        $this->delete(
            '/v2/vpns/' . $this->faker->uuid,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Not found',
                'detail' => 'No Vpn with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $vpns = $this->createVpn();
        $this->delete(
            '/v2/vpns/' . $vpns->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $vpnItem = Vpn::withTrashed()->findOrFail($vpns->getKey());
        $this->assertNotNull($vpnItem->deleted_at);
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
        $router = factory(Router::class, 1)->create()->first();
        $router->save();
        $router->refresh();
        return $router;
    }

}
