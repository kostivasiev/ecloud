<?php

namespace Tests\V2\FirewallRulePort;

use App\Events\V2\FirewallRulePort\Deleted;
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

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

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
        $this->firewallRulePort = factory(FirewallRulePort::class)->create([
            'firewall_rule_id' => $this->firewallRule->id,
        ]);
    }

    public function testSuccessfulDelete()
    {
        $this->delete(
            '/v2/firewall-rule-ports/' . $this->firewallRulePort->id,
            [],
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $firewallRulePort = FirewallRulePort::withTrashed()->findOrFail($this->firewallRulePort->id);
        $this->assertNotNull($firewallRulePort->deleted_at);

        Event::assertDispatched(Deleted::class, function ($job) {
            return $job->model->id === $this->firewallRulePort->id;
        });
    }

}
