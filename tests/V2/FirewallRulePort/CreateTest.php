<?php

namespace Tests\V2\FirewallRulePort;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
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
    protected $availabilityZone;
    protected $firewallPolicy;
    protected $firewallRule;
    protected $region;
    protected $router;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);
        $this->firewallPolicy = factory(FirewallPolicy::class)->create([
            'router_id' => $this->router->getKey(),
        ]);
        $this->firewallRule = factory(FirewallRule::class)->create([
            'firewall_policy_id' => $this->firewallPolicy->getKey(),
        ]);
    }

    public function testValidDataSucceeds()
    {
        $this->post('/v2/firewall-rule-ports', [
            'firewall_rule_id' => $this->firewallRule->getKey(),
            'protocol' => 'TCP',
            'source' => '443',
            'destination' => '555'
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write'
        ])->seeInDatabase(
            'firewall_rule_ports',
            [
                'firewall_rule_id' => $this->firewallRule->getKey(),
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '555'
            ],
            'ecloud'
        )->assertResponseStatus(201);
    }

    public function testValidICMPDataSucceeds()
    {
        $this->post('/v2/firewall-rule-ports', [
            'firewall_rule_id' => $this->firewallRule->getKey(),
            'protocol' => 'ICMPv4'
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write'
        ])->seeInDatabase(
            'firewall_rule_ports',
            [
                'firewall_rule_id' => $this->firewallRule->getKey(),
                'protocol' => 'ICMPv4',
            ],
            'ecloud'
        )->assertResponseStatus(201);
    }
}
