<?php

namespace Tests\V2\Router;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Models\V2\Vpn;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeletionRulesTest extends TestCase
{
    use DatabaseMigrations;

    protected AvailabilityZone $availability_zone;
    protected Region $region;
    protected Router $router;
    protected Vpc $vpc;
    protected Vpn $vpn;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ]);
        $this->vpn = factory(Vpn::class)->create([
            'router_id' => $this->router->getKey(),
        ]);
    }

    public function testFailedDeletion()
    {
        $this->delete(
            '/v2/routers/' . $this->router->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson([
            'detail' => 'Active resources exist for this item',
        ])->assertResponseStatus(412);
        $router = Router::withTrashed()->findOrFail($this->router->getKey());
        $this->assertNull($router->deleted_at);
    }
}
