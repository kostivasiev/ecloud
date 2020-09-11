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

    /** @var Region */
    private $region;

    /** @var Vpc */
    private $vpc;

    /** @var Router */
    private $router;

    /** @var Network */
    private $network;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ]);
        $this->network = factory(Network::class)->create([
            'router_id' => $this->router->getKey(),
        ]);
    }

    public function testNoPermsIsDenied()
    {
        $this->patch('/v2/networks/' . $this->network->getKey(),[
            'name' => 'Manchester Network',
        ])->seeJson([
            'title'  => 'Unauthorised',
            'detail' => 'Unauthorised',
            'status' => 401,
        ])->assertResponseStatus(401);
    }

    public function testNullNameIsDenied()
    {
        $this->patch('/v2/networks/' . $this->network->getKey(), [
            'name' => '',
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title'  => 'Validation Error',
            'detail' => 'The name field, when specified, cannot be null',
            'status' => 422,
            'source' => 'name'
        ])->assertResponseStatus(422);
    }


    public function testInvalidRouterIdIsFailed()
    {
        $this->patch('/v2/networks/' . $this->network->getKey(), [
            'name' => 'Manchester Network',
            'router_id' => 'x'
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title'  => 'Validation Error',
            'detail' => 'The specified router id was not found',
            'status' => 422,
            'source' => 'router_id'
        ])->assertResponseStatus(422);
    }

    public function testNotOwnedRouterIdIsFailed()
    {
        $this->vpc->reseller_id = 3;
        $this->vpc->save();
        $this->patch('/v2/networks/' . $this->network->getKey(),[
            'name' => 'Manchester Network',
            'router_id' => $this->router->getKey()
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title'  => 'Validation Error',
            'detail' => 'The specified router id was not found',
            'status' => 422,
            'source' => 'router_id'
        ])->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $this->patch('/v2/networks/' . $this->network->getKey(), [
            'name' => 'expected',
            'router_id' => $this->router->getKey()
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(200);
        $this->assertEquals('expected', Network::findOrFail($this->network->getKey())->name);
    }
}
