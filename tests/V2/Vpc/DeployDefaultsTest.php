<?php

namespace Tests\V2\Vpc;

use App\Models\V2\FirewallPolicy;
use App\Models\V2\Network;
use App\Models\V2\Router;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeployDefaultsTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();

        $this->nsxServiceMock()->shouldReceive('patch')
            ->withArgs([
                'policy/api/v1/infra/domains/default/gateway-policies/fwp-test',
                [
                    'json' => [
                        'id' => 'fwp-test',
                        'display_name' => 'name',
                        'description' => 'name',
                        'sequence_number' => 10,
                        'rules' => [],
                    ]
                ]
            ])
            ->andReturn(new Response(200, [], ''));
        $this->nsxServiceMock()->shouldReceive('get')
            ->withArgs(['policy/api/v1/infra/domains/default/gateway-policies/?include_mark_for_delete_objects=true'])
            ->andReturn(new Response(200, [], json_encode(['results' => [['id' => 0]]])));
        $this->nsxServiceMock()->shouldReceive('get')
            ->withArgs(['/policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/gateway-policies/fwp-test'])
            ->andReturn(new Response(200, [], json_encode(['results' => [['id' => 0]]])));
        $this->nsxServiceMock()->shouldReceive('delete')
            ->withArgs(['policy/api/v1/infra/domains/default/gateway-policies/fwp-test'])
            ->andReturn(new Response(204, [], ''));

        app()->bind(Router::class, function () {
            return new Router([
                'id' => 'rtr-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);
        });

        app()->bind(FirewallPolicy::class, function () {
            return new FirewallPolicy([
                'id' => 'fwp-test',
                'router_id' => 'rtr-test',
            ]);
        });
    }

    public function testInvalidVpcId()
    {
        $this->post('/v2/vpcs/x/deploy-defaults', [], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write'
        ])->seeJson([
            'title' => 'Not found',
            'detail' => 'No Vpc with that ID was found'
        ])->assertResponseStatus(404);
    }

    public function testValidDeploy()
    {
        $this->post('/v2/vpcs/' . $this->vpc()->id . '/deploy-defaults', [], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write'
        ])->assertResponseStatus(202);

        // Check the relationships are intact
        $router = $this->vpc()->routers()->first();
        $this->assertNotNull($router);
        $this->assertNotNull(Network::where('router_id', '=', $router->id)->first());

        Event::assertDispatched(\App\Events\V2\FirewallPolicy\Saved::class);

        // Check the relationships are intact
        $policies = config('firewall.policies');

        $firewallPolicies = FirewallPolicy::where('router_id', $router->id);

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
