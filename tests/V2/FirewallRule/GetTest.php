<?php

namespace Tests\V2\FirewallRule;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected FirewallPolicy $firewallPolicy;
    protected FirewallRule $firewallRule;
    protected Region $region;
    protected Router $router;
    protected Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create();
        factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);
        $this->firewallPolicy = factory(FirewallPolicy::class)->create([
            'router_id' => $this->router->id,
        ]);
        $this->firewallRule = factory(FirewallRule::class)->create([
            'firewall_policy_id' => $this->firewallPolicy->getKey(),
        ])->first();
        $this->firewallRulePort = factory(FirewallRulePort::class)->create([
            'firewall_rule_id' => $this->firewallRule->getKey(),
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/firewall-rules',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'firewall_policy_id' => $this->firewallPolicy->getKey(),
                'source' => $this->firewallRule->source,
                'destination' => $this->firewallRule->destination,
                'action' => $this->firewallRule->action,
                'direction' => $this->firewallRule->direction,
                'enabled' => $this->firewallRule->enabled,
                'id' => $this->firewallRule->id,
                'name' => $this->firewallRule->name,
                'sequence' => (string)$this->firewallRule->sequence,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get(
            '/v2/firewall-rules/' . $this->firewallRule->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->firewallRule->id,
                'name' => $this->firewallRule->name,
                'sequence' => (string)$this->firewallRule->sequence,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetPortsCollection()
    {
        $this->get(
            '/v2/firewall-rules/' . $this->firewallRule->getKey() . '/ports',
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'firewall_rule_id' => $this->firewallRule->getKey(),
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '555'
            ])
            ->assertResponseStatus(200);
    }
}
