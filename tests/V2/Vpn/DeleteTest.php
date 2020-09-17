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

    protected \Faker\Generator $faker;
    protected $availability_zone;
    protected $region;
    protected $router;
    protected $vpc;
    protected $vpn;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create([
            'name' => $this->faker->country(),
        ]);
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id'          => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ]);
        $this->vpn = factory(Vpn::class)->create([
            'router_id' => $this->router->id,
        ]);
    }

    public function testSuccessfulDelete()
    {
        $this->delete(
            '/v2/vpns/' . $this->vpn->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $vpnItem = Vpn::withTrashed()->findOrFail($this->vpn->getKey());
        $this->assertNotNull($vpnItem->deleted_at);
    }
}
