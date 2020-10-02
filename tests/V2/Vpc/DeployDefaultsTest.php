<?php

namespace Tests\V2\Vpc;

use App\Events\V2\RouterAvailabilityZoneAttach;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Network;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class DeployDefaultsTest extends TestCase
{
    use DatabaseMigrations;

    /** @var Region */
    private $region;

    /** @var AvailabilityZone */
    private $availabilityZone;

    /** @var Vpc */
    private $vpc;

    public function setUp(): void
    {
        parent::setUp();

        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
    }

    public function testInvalidVpcId()
    {
        $this->post('/v2/vpcs/x/deploy-defaults', [], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups'    => 'ecloud.write'
        ])->seeJson([
            'title'  => 'Not found',
            'detail' => 'No Vpc with that ID was found'
        ])->assertResponseStatus(404);
    }

    public function testValidDeploy()
    {
        $this->post('/v2/vpcs/' . $this->vpc->getKey() . '/deploy-defaults', [], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups'    => 'ecloud.write'
        ])->assertResponseStatus(202);

        // Check the relationships are intact
        $router = $this->vpc->routers()->first();
        $this->assertNotNull($router);
        $this->assertNotNull(Network::where('router_id', '=', $router->getKey())->first());
    }
}
