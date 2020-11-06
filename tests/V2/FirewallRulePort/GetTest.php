<?php

namespace Tests\V2\FirewallRulePort;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected FirewallPolicy $firewallPolicy;
    protected FirewallRule $firewallRule;
    protected FirewallRulePort $firewallRulePort;
    protected Region $region;
    protected Router $router;
    protected Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
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
        ]);
        $this->firewallRulePort = factory(FirewallRulePort::class)->create([
            'firewall_rule_id' => $this->firewallRule->getKey(),
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/firewall-rule-ports',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'firewall_rule_id' => $this->firewallRulePort->firewall_rule_id,
                'protocol' => $this->firewallRulePort->protocol,
                'source' => $this->firewallRulePort->source,
                'destination' => $this->firewallRulePort->destination
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get(
            '/v2/firewall-rule-ports/' . $this->firewallRulePort->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'firewall_rule_id' => $this->firewallRulePort->firewall_rule_id,
                'protocol' => $this->firewallRulePort->protocol,
                'source' => $this->firewallRulePort->source,
                'destination' => $this->firewallRulePort->destination
            ])
            ->assertResponseStatus(200);
    }
}
