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

class DeleteTest extends TestCase
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
            'name' => 'Demo firewall rule 1',
            'router_id' => $this->router->getKey(),
            'firewall_policy_id' => $this->firewall_policy->getKey(),
            'service_type' => 'TCP',
            'source' => '192.168.100.1',
            'source_ports' => '80,443',
            'destination' => '212.22.18.10',
            'destination_ports' => '8080,4043',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ]);
    }

    public function testFailInvalidId()
    {
        $this->delete(
            '/v2/firewall-rules/' . $this->faker->uuid,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Not found',
                'detail' => 'No Firewall Rule with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $this->delete(
            '/v2/firewall-rules/' . $this->firewall_rule->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $instance = FirewallRule::withTrashed()->findOrFail($this->firewall_rule->getKey());
        $this->assertNotNull($instance->deleted_at);
    }

}
