<?php

namespace Tests\V2\FirewallPolicy;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeletionRulesTest extends TestCase
{
    use DatabaseMigrations;

    protected FirewallPolicy $policy;
    protected Region $region;
    protected Router $router;
    protected FirewallRule $rule;
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
        $this->policy = factory(FirewallPolicy::class)->create([
            'router_id' => $this->router->getKey(),
        ])->first();
        $this->rule = factory(FirewallRule::class)->create([
            'firewall_policy_id' => $this->policy->getKey(),
            'router_id' => $this->router->getKey(),
        ]);
    }

    public function testFailedDeletion()
    {
        $this->delete(
            '/v2/firewall-policies/' . $this->policy->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson([
            'detail' => 'Active resources exist for this item',
        ])->assertResponseStatus(412);
        $firewallPolicy = FirewallPolicy::withTrashed()->findOrFail($this->policy->getKey());
        $this->assertNull($firewallPolicy->deleted_at);
    }
}