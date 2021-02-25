<?php

namespace Tests\V2\Network;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Network;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected $region;
    protected $vpc;
    protected $router;
    protected $network;
    protected $availabilityZone;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'availability_zone_id' => $this->availabilityZone->id
        ]);
        $this->network = factory(Network::class)->create([
            'router_id' => $this->router->getKey(),
        ]);
    }

    public function testNotOwnedRouterIdIsFailed()
    {
        $this->vpc->reseller_id = 3;
        $this->vpc->save();
        $this->patch(
            '/v2/networks/' . $this->network->getKey(),
            [
                'name' => 'Manchester Network',
                'router_id' => $this->router->getKey()
            ],
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ])->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The specified router id was not found',
            'status' => 422,
            'source' => 'router_id'
        ])->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $this->patch(
            '/v2/networks/' . $this->network->getKey(),
            [
                'name' => 'expected',
                'router_id' => $this->router->getKey()
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ])->assertResponseStatus(200);

        $network = Network::findOrFail($this->network->getKey());
        $this->assertEquals('expected', $network->name);
    }
}
