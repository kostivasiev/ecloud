<?php

namespace Tests\V2\FirewallRule;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FirewallPolicy;
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
    protected FirewallPolicy $firewall_policy;
    protected FirewallRule $firewall_rule;
    protected Region $region;
    protected Vpc $vpc;
    protected Router $router;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
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
        $this->firewall_policy = factory(FirewallPolicy::class)->create([
            'router_id' => $this->router->getKey(),
        ]);
        $this->firewall_rule = factory(FirewallRule::class)->create([
            'firewall_policy_id' => $this->firewall_policy->getKey(),
        ]);
    }

    public function testValidDataSucceeds()
    {
        $this->patch(
            '/v2/firewall-rules/' . $this->firewall_rule->id,
            [
                'name' => 'Changed',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeInDatabase('firewall_rules',
                [
                    'id' => $this->firewall_rule->id,
                    'name' => 'Changed'
                ],
                'ecloud')
            ->assertResponseStatus(200);
    }
}
