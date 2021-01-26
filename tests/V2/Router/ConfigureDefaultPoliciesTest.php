<?php

namespace Tests\V2\Router;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class ConfigureDefaultPoliciesTest extends TestCase
{
    use DatabaseMigrations;

    protected $region;
    protected $router;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();
        factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->id
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->id
        ]);
    }

    public function testConfigureDefaults()
    {
        $this->post('/v2/routers/' . $this->router->id . '/configure-default-policies', [], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write'
        ])->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\FirewallPolicy\Saved::class);

        // Check the relationships are intact
        $policies = config('firewall.policies');

        $firewallPolicies = FirewallPolicy::where('router_id', $this->router->id);

        $this->assertEquals(count($policies), $firewallPolicies->count());

        $firewallPolicy = $firewallPolicies->first();

        // Verify Policy
        $this->assertEquals($policies[0]['name'], $firewallPolicy->name);

        // Verify Rule
        $firewallRule = $firewallPolicy->firewallRules()->first();
        $this->assertEquals($policies[0]['rules'][0]['name'], $firewallRule->name);

        // Verify Port
        $firewallRulePort = $firewallRule->firewallRulePorts()->first();
        $this->assertEquals($policies[0]['rules'][0]['ports'][0]['protocol'], $firewallRulePort->protocol);
    }
}
