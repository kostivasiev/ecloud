<?php

namespace Tests\V2\FirewallRulePort;

use App\Events\V2\FirewallPolicy\Saved as FirewallPolicySaved;
use App\Events\V2\FirewallRulePort\Saved as FirewallRulePortSaved;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected $firewallPolicy;
    protected $firewallRule;
    protected $firewallRulePort;
    protected $region;
    protected $vpc;
    protected $router;

    public function setUp(): void
    {
        parent::setUp();
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
        $this->firewallRulePort = factory(FirewallRulePort::class)->create([
            'firewall_rule_id' => $this->firewallRule->getKey(),
        ]);
    }

    public function testValidDataSucceeds()
    {
        $this->patch(
            '/v2/firewall-rule-ports/' . $this->firewallRulePort->getKey(),
            [
                'name' => 'Changed',
                'protocol' => 'UDP',
                'source' => '10.0.0.1',
                'destination' => '192.168.1.2'
            ],
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'firewall_rule_ports',
            [
                'id' => $this->firewallRulePort->getKey(),
                'name' => 'Changed',
                'protocol' => 'UDP',
                'source' => '10.0.0.1',
                'destination' => '192.168.1.2'
            ],
            'ecloud'
        )->assertResponseStatus(200);

        Event::assertDispatched(FirewallPolicySaved::class, function ($job) {
            return $job->model->id === $this->firewallPolicy->getKey();
        });

        Event::assertDispatched(FirewallRulePortSaved::class, function ($job) {
            return $job->model->id === $this->firewallRulePort->getKey();
        });
    }

    public function testUpdateWithICMPValues()
    {
        $this->patch(
            '/v2/firewall-rule-ports/' . $this->firewallRulePort->getKey(),
            [
                'name' => 'Changed',
                'protocol' => 'ICMPv4',
            ],
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'firewall_rule_ports',
            [
                'id' => $this->firewallRulePort->getKey(),
                'name' => 'Changed',
                'protocol' => 'ICMPv4',
                'source' => null,
                'destination' => null
            ],
            'ecloud'
        )->assertResponseStatus(200);

        Event::assertDispatched(FirewallPolicySaved::class, function ($job) {
            return $job->model->id === $this->firewallPolicy->getKey();
        });

        Event::assertDispatched(FirewallRulePortSaved::class, function ($job) {
            return $job->model->id === $this->firewallRulePort->getKey();
        });
    }
}
