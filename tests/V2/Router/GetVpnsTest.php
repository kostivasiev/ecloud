<?php

namespace Tests\V2\Router;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Models\V2\VpnService;
use Faker\Factory as Faker;
use Tests\TestCase;

class GetVpnsTest extends TestCase
{
    protected \Faker\Generator $faker;
    protected Region $region;
    protected Router $router;
    protected Vpc $vpc;
    protected VpnService $vpnService;
    protected AvailabilityZone $availabilityZone;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = Region::factory()->create();
        $this->availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $this->region->id,
        ]);
        $this->router = Router::factory()->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id
        ]);
        $this->vpnService = VpnService::factory()->create([
            'router_id' => $this->router->id,
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/routers/'.$this->router->id.'/vpns',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->assertJsonFragment([
                'id'                   => $this->vpnService->id,
                'router_id'            => $this->vpnService->router_id,
            ])
            ->assertStatus(200);
    }

}
