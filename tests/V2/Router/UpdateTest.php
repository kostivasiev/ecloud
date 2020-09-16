<?php

namespace Tests\V2\Router;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    /** @var Region */
    private $region;

    /** @var Vpc */
    private $vpc;

    /** @var Router */
    private $router;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();
        factory(AvailabilityZone::class)->create([
            'code'               => 'TIM1',
            'name'               => 'Tims Region 1',
            'datacentre_site_id' => 1,
            'region_id'          => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);
    }

    public function testNullNameIsDenied()
    {
        $this->patch('/v2/routers/' . $this->router->getKey(), [
            'name' => '',
            'vpc_id' => $this->vpc->getKey()
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The name field, when specified, cannot be null',
            'status' => 422,
            'source' => 'name'
        ])->assertResponseStatus(422);
    }

    public function testInvalidVpcIdIdIsFailed()
    {
        $this->post('/v2/routers', [
            'name' => 'Manchester Router 2',
            'vpc_id' => 'x',
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The specified vpc id was not found',
            'status' => 422,
            'source' => 'vpc_id'
        ])->assertResponseStatus(422);
    }

    public function testNotOwnedVpcIdIdIsFailed()
    {
        $this->post('/v2/routers', [
                'name' => 'Manchester Router 2',
                'vpc_id' => 'x',
            ], [
                'X-consumer-custom-id' => '2-0',
                'X-consumer-groups' => 'ecloud.write',
            ])->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The specified vpc id was not found',
                'status' => 422,
                'source' => 'vpc_id'
            ])->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $this->patch('/v2/routers/' . $this->router->getKey(), [
            'name' => 'expected',
            'vpc_id' => $this->vpc->getKey()
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(200);
        $this->assertEquals('expected', Router::findOrFail($this->router->getKey())->name);
    }
}
