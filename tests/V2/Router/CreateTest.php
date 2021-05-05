<?php

namespace Tests\V2\Router;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\RouterThroughput;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    private Region $region;

    private AvailabilityZone $availabilityZone;

    private Vpc $vpc;

    private Router $router;

    private RouterThroughput $routerThroughput;

    public function setUp(): void
    {
        parent::setUp();

        $this->routerThroughput = factory(RouterThroughput::class)->create([
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);
    }

    public function testNotOwnedVpcIdIdIsFailed()
    {
        $this->post(
            '/v2/routers',
            [
                'name' => 'Manchester Router 2',
                'vpc_id' => 'x',
            ],
            [
                'X-consumer-custom-id' => '2-0',
                'X-consumer-groups' => 'ecloud.write',
            ])
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The specified vpc id was not found',
                'status' => 422,
                'source' => 'vpc_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'name' => 'Manchester Router 1',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'router_throughput_id' => $this->routerThroughput->id
        ];
        $this->post(
            '/v2/routers',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
           ->seeInDatabase('routers', $data, 'ecloud')
            ->assertResponseStatus(202);
    }
}
