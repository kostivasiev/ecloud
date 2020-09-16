<?php

namespace Tests\V2\Vpn;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Models\V2\Vpn;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;
    protected $region;
    protected $vpc;
    protected $availability_zone;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create([
            'name' => $this->faker->country(),
        ]);
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'code'               => 'TIM1',
            'name'               => 'Tims Region 1',
            'datacentre_site_id' => 1,
            'region_id'          => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ])->refresh();
    }

    public function testNoPermsIsDenied()
    {
        $router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ]);
        $vpn = factory(Vpn::class)->create([
            'router_id' => $router->id,
        ]);
        $this->delete(
            '/v2/vpns/' . $vpn->getKey(),
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
                'X-consumer-groups'    => 'ecloud.write',
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
        $router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ]);
        $vpn = factory(Vpn::class)->create([
            'router_id' => $router->id,
        ]);
        $this->delete(
            '/v2/vpns/' . $vpn->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $vpnItem = Vpn::withTrashed()->findOrFail($vpn->getKey());
        $this->assertNotNull($vpnItem->deleted_at);
    }
}
