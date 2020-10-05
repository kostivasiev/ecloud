<?php

namespace Tests\V2\FirewallRule;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FirewallRule;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $firewall_rule;
    protected $region;
    protected $vpc;
    protected $router;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->firewall_rule = factory(FirewallRule::class)->create();
        $this->region = factory(Region::class)->create();
        factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'reseller_id' => 3,
            'region_id' => $this->region->getKey()
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);
    }

    public function testNotOwnedRouterIdIsFailed()
    {
        $this->patch(
            '/v2/firewall-rules/' . $this->firewall_rule->id,
            [
                'name' => $this->faker->word(),
                'router_id' => $this->router->getKey()
            ],
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The specified router id was not found',
                'status' => 422,
                'source' => 'router_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        $this->patch(
            '/v2/firewall-rules/' . $this->firewall_rule->id,
            [
                'name' => 'Demo firewall rule 1',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(200);

        $availabilityZoneId = (json_decode($this->response->getContent()))->data->id;
        $this->seeJson([
            'id' => $availabilityZoneId,
        ]);
    }

}
