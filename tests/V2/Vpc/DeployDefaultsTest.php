<?php

namespace Tests\V2\Vpc;

use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\Network;
use App\Models\V2\Router;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeployDefaultsTest extends TestCase
{
    use DatabaseMigrations;

    private $firewallPolicyCount;
    private $firewallRuleCount;
    private $firewallRulePortCount;

    public function setUp(): void
    {
        parent::setUp();

        $this->nsxServiceMock()->shouldReceive('get')
            ->withArgs(['policy/api/v1/infra/domains/default/gateway-policies/?include_mark_for_delete_objects=true'])
            ->andReturn(
                new Response(200, [], json_encode(['results' => [['id' => 0]]])),
                new Response(200, [], json_encode(['results' => [['id' => 0]]])),
                new Response(200, [], json_encode(['results' => [['id' => 0]]]))
            );

        $this->nsxServiceMock()->shouldReceive('patch')
            ->withArgs([
                'policy/api/v1/infra/domains/default/gateway-policies/fwp-test-1',
                [
                    'json' => [
                        'id' => 'fwp-test-1',
                        'display_name' => 'Infrastructure',
                        'description' => 'Infrastructure',
                        'sequence_number' => 0,
                        'rules' => [
                            [
                                'action' => 'ALLOW',
                                'resource_type' => 'Rule',
                                'id' => 'fwr-test-1',
                                'display_name' => 'Ping',
                                'sequence_number' => '0',
                                'sources_excluded' => false,
                                'destinations_excluded' => false,
                                'source_groups' => ['ANY'],
                                'destination_groups' => ['ANY'],
                                'services' => ['ANY'],
                                'service_entries' => [
                                    [
                                        'id' => 'fwrp-test-1',
                                        'icmp_type' => 8,
                                        'resource_type' => 'ICMPTypeServiceEntry',
                                        'protocol' => 'ICMPv4',
                                    ]
                                ],
                                'profiles' => ['ANY'],
                                'logged' => true,
                                'scope' => ['/infra/tier-1s/rtr-test'],
                                'disabled' => false,
                                'notes' => '',
                                'direction' => 'OUT',
                                'tag' => '',
                                'ip_protocol' => 'IPV4_IPV6',
                            ],
                            [
                                'action' => 'ALLOW',
                                'resource_type' => 'Rule',
                                'id' => 'fwr-test-2',
                                'display_name' => 'DNS',
                                'sequence_number' => '0',
                                'sources_excluded' => false,
                                'destinations_excluded' => false,
                                'source_groups' => ['ANY'],
                                'destination_groups' => ['ANY'],
                                'services' => ['ANY'],
                                'service_entries' => [
                                    [
                                        'id' => 'fwrp-test-2',
                                        'l4_protocol' => 'UDP',
                                        'resource_type' => 'L4PortSetServiceEntry',
                                        'source_ports' => [],
                                        'destination_ports' => ['53']
                                    ],
                                    [
                                        'id' => 'fwrp-test-3',
                                        'l4_protocol' => 'TCP',
                                        'resource_type' => 'L4PortSetServiceEntry',
                                        'source_ports' => [],
                                        'destination_ports' => ['53']
                                    ]
                                ],
                                'profiles' => ['ANY'],
                                'logged' => true,
                                'scope' => ['/infra/tier-1s/rtr-test'],
                                'disabled' => false,
                                'notes' => '',
                                'direction' => 'OUT',
                                'tag' => '',
                                'ip_protocol' => 'IPV4_IPV6',
                            ],
                            [
                                'action' => 'ALLOW',
                                'resource_type' => 'Rule',
                                'id' => 'fwr-test-3',
                                'display_name' => 'NTP',
                                'sequence_number' => '0',
                                'sources_excluded' => false,
                                'destinations_excluded' => false,
                                'source_groups' => ['ANY'],
                                'destination_groups' => ['ANY'],
                                'services' => ['ANY'],
                                'service_entries' => [
                                    [
                                        'id' => 'fwrp-test-4',
                                        'l4_protocol' => 'UDP',
                                        'resource_type' => 'L4PortSetServiceEntry',
                                        'source_ports' => [],
                                        'destination_ports' => ['123']
                                    ],
                                    [
                                        'id' => 'fwrp-test-5',
                                        'l4_protocol' => 'TCP',
                                        'resource_type' => 'L4PortSetServiceEntry',
                                        'source_ports' => [],
                                        'destination_ports' => ['123'],
                                    ]
                                ],
                                'profiles' => ['ANY'],
                                'logged' => true,
                                'scope' => ['/infra/tier-1s/rtr-test'],
                                'disabled' => false,
                                'notes' => '',
                                'direction' => 'OUT',
                                'tag' => '',
                                'ip_protocol' => 'IPV4_IPV6',
                            ],
                            [
                                'action' => 'ALLOW',
                                'resource_type' => 'Rule',
                                'id' => 'fwr-test-4',
                                'display_name' => 'HTTP/S',
                                'sequence_number' => '0',
                                'sources_excluded' => false,
                                'destinations_excluded' => false,
                                'source_groups' => ['ANY'],
                                'destination_groups' => ['ANY'],
                                'services' => ['ANY'],
                                'service_entries' => [
                                    [
                                        'id' => 'fwrp-test-6',
                                        'l4_protocol' => 'TCP',
                                        'resource_type' => 'L4PortSetServiceEntry',
                                        'source_ports' => [],
                                        'destination_ports' => ['80', '443'],
                                    ]
                                ],
                                'profiles' => ['ANY'],
                                'logged' => true,
                                'scope' => ['/infra/tier-1s/rtr-test'],
                                'disabled' => false,
                                'notes' => '',
                                'direction' => 'OUT',
                                'tag' => '',
                                'ip_protocol' => 'IPV4_IPV6',
                            ]
                        ]
                    ]
                ]
            ])
            ->andReturn(new Response(200, [], ''));

        $this->nsxServiceMock()->shouldReceive('get')
            ->withArgs(['policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/gateway-policies/fwp-test-1'])
            ->andReturn(new Response(200, [], json_encode(['results' => [['id' => 0]]])));

        $this->nsxServiceMock()->shouldReceive('patch')
            ->withArgs([
                'policy/api/v1/infra/domains/default/gateway-policies/fwp-test-2',
                [
                    'json' => [
                        'id' => 'fwp-test-2',
                        'display_name' => 'Remote Access',
                        'description' => 'Remote Access',
                        'sequence_number' => 0,
                        'rules' => [
                            [
                                'action' => 'ALLOW',
                                'resource_type' => 'Rule',
                                'id' => 'fwr-test-5',
                                'display_name' => 'RDP',
                                'sequence_number' => '0',
                                'sources_excluded' => false,
                                'destinations_excluded' => false,
                                'source_groups' => ['ANY'],
                                'destination_groups' => ['ANY'],
                                'services' => ['ANY'],
                                'service_entries' => [
                                    [
                                        'id' => 'fwrp-test-7',
                                        'l4_protocol' => 'TCP',
                                        'resource_type' => 'L4PortSetServiceEntry',
                                        'source_ports' => [],
                                        'destination_ports' => ['3389'],
                                    ]
                                ],
                                'profiles' => ['ANY'],
                                'logged' => true,
                                'scope' => ['/infra/tier-1s/rtr-test'],
                                'disabled' => false,
                                'notes' => '',
                                'direction' => 'IN',
                                'tag' => '',
                                'ip_protocol' => 'IPV4_IPV6',
                            ],
                            [
                                'action' => 'ALLOW',
                                'resource_type' => 'Rule',
                                'id' => 'fwr-test-6',
                                'display_name' => 'SSH',
                                'sequence_number' => '0',
                                'sources_excluded' => false,
                                'destinations_excluded' => false,
                                'source_groups' => ['ANY'],
                                'destination_groups' => ['ANY'],
                                'services' => ['ANY'],
                                'service_entries' => [
                                    [
                                        'id' => 'fwrp-test-8',
                                        'l4_protocol' => 'TCP',
                                        'resource_type' => 'L4PortSetServiceEntry',
                                        'source_ports' => [],
                                        'destination_ports' => ['2020'],
                                    ],
                                ],
                                'profiles' => ['ANY'],
                                'logged' => true,
                                'scope' => ['/infra/tier-1s/rtr-test'],
                                'disabled' => false,
                                'notes' => '',
                                'direction' => 'IN',
                                'tag' => '',
                                'ip_protocol' => 'IPV4_IPV6',
                            ],
                        ]
                    ]
                ]
            ])
            ->andReturn(new Response(200, [], ''));

        $this->nsxServiceMock()->shouldReceive('get')
            ->withArgs(['policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/gateway-policies/fwp-test-2'])
            ->andReturn(new Response(200, [], json_encode(['results' => [['id' => 0]]])));

        $this->nsxServiceMock()->shouldReceive('patch')
            ->withArgs([
                'policy/api/v1/infra/domains/default/gateway-policies/fwp-test-3',
                [
                    'json' => [
                        'id' => 'fwp-test-3',
                        'display_name' => 'Web Services',
                        'description' => 'Web Services',
                        'sequence_number' => 0,
                        'rules' => [
                            [
                                'action' => 'ALLOW',
                                'resource_type' => 'Rule',
                                'id' => 'fwr-test-7',
                                'display_name' => 'HTTP/S',
                                'sequence_number' => '0',
                                'sources_excluded' => false,
                                'destinations_excluded' => false,
                                'source_groups' => ['ANY'],
                                'destination_groups' => ['ANY'],
                                'services' => ['ANY'],
                                'service_entries' => [
                                    [
                                        'id' => 'fwrp-test-9',
                                        'l4_protocol' => 'TCP',
                                        'resource_type' => 'L4PortSetServiceEntry',
                                        'source_ports' => [],
                                        'destination_ports' => ['80', '443'],
                                    ]
                                ],
                                'profiles' => ['ANY'],
                                'logged' => true,
                                'scope' => ['/infra/tier-1s/rtr-test'],
                                'disabled' => false,
                                'notes' => '',
                                'direction' => 'IN',
                                'tag' => '',
                                'ip_protocol' => 'IPV4_IPV6',
                            ],
                        ]
                    ]
                ]
            ])
            ->andReturn(new Response(200, [], ''));

        $this->nsxServiceMock()->shouldReceive('get')
            ->withArgs(['policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/gateway-policies/fwp-test-3'])
            ->andReturn(new Response(200, [], json_encode(['results' => [['id' => 0]]])));

        app()->bind(Router::class, function () {
            return new Router([
                'id' => 'rtr-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);
        });

        app()->bind(FirewallPolicy::class, function () {
            $this->firewallPolicyCount++;
            return new FirewallPolicy([
                'id' => 'fwp-test-' . $this->firewallPolicyCount,
                'router_id' => 'rtr-test',
            ]);
        });

        app()->bind(FirewallRule::class, function () {
            $this->firewallRuleCount++;
            return new FirewallRule([
                'id' => 'fwr-test-' . $this->firewallRuleCount,
            ]);
        });

        app()->bind(FirewallRulePort::class, function () {
            $this->firewallRulePortCount++;
            return new FirewallRulePort([
                'id' => 'fwrp-test-' . $this->firewallRulePortCount,
            ]);
        });

        app()->bind(Network::class, function () {
            return new Network([
                'id' => 'net-test',
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
