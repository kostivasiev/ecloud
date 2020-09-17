<?php

namespace Tests\V2\FirewallRule;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;
    protected $vpc;
    protected $router;
    protected $availability_zone;
    protected $region;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id'          => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);

        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);
    }

    public function testNotOwnedRouterIsFailed()
    {
        $this->post(
            '/v2/firewall-rules',
            [
                'name' => 'Demo firewall rule 1',
                'router_id' => $this->router->getKey()
            ],
            [
                'X-consumer-custom-id' => '2-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The specified router id was not found',
                'status' => 422,
                'source' => 'router_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        $this->post(
            '/v2/firewall-rules',
            [
                'name' => 'Demo firewall rule 1',
                'router_id' => $this->router->getKey()
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )->assertResponseStatus(201);

        $availabilityZoneId = (json_decode($this->response->getContent()))->data->id;
        $this->seeJson([
            'id' => $availabilityZoneId,
        ]);
    }

}
