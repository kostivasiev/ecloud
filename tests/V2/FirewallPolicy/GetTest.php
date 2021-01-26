<?php

namespace Tests\V2\FirewallPolicy;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
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
    protected Region $region;
    protected Router $router;
    protected Vpc $vpc;

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
        $this->firewallPolicy = factory(FirewallPolicy::class)->create([
            'router_id' => $this->router->id,
        ]);
        $this->firewallRule = factory(FirewallRule::class)->create([
            'firewall_policy_id' => $this->firewallPolicy->id,
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/firewall-policies',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->firewallPolicy->id,
                'name' => $this->firewallPolicy->name,
                'sequence' => $this->firewallPolicy->sequence,
                'router_id' => $this->router->id,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/firewall-policies/' . $this->firewallPolicy->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->firewallPolicy->id,
                'name' => $this->firewallPolicy->name,
                'sequence' => $this->firewallPolicy->sequence,
                'router_id' => $this->router->id,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetFirewallPolicyFirewallRules()
    {
        $this->get(
            '/v2/firewall-policies/' . $this->firewallPolicy->id . '/firewall-rules',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->firewallRule->id,
                'firewall_policy_id' => $this->firewallPolicy->id
            ])
            ->assertResponseStatus(200);
    }
}
