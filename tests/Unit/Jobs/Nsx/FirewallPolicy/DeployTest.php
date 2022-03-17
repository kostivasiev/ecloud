<?php

namespace Tests\Unit\Jobs\Nsx\FirewallPolicy;

use App\Jobs\Nsx\FirewallPolicy\Deploy;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeployTest extends TestCase
{
    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->firewallPolicy());
            $this->task->save();
        });
    }

    public function testPolicyNoRulesDeploys()
    {
        $this->nsxServiceMock()->shouldReceive('patch')
            ->withArgs([
                '/policy/api/v1/infra/domains/default/gateway-policies/fwp-test',
                [
                    'json' => [
                        "id" => "fwp-test",
                        "display_name" => "fwp-test",
                        "description" => "name",
                        "sequence_number" => 10,
                        "rules" => [],
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testPolicyWithRulesDeploys()
    {
        $this->firewallPolicy()->firewallRules()->create([
            'id' => 'fwr-test-1',
            'name' => 'fwr-test-1',
            'sequence' => 2,
            'source' => '192.168.1.1, 192.168.1.2',
            'destination' => '192.168.1.3,192.168.1.4',
            'action' => 'REJECT',
            'direction' => 'IN',
            'enabled' => true,
        ]);

        $this->nsxServiceMock()->shouldReceive('patch')
            ->withArgs([
                '/policy/api/v1/infra/domains/default/gateway-policies/fwp-test',
                [
                    'json' => [
                        "id" => "fwp-test",
                        "display_name" => "fwp-test",
                        "description" => "name",
                        "sequence_number" => 10,
                        "rules" => [
                             [
                                "action" => "REJECT",
                                "resource_type" => "Rule",
                                "id" => "fwr-test-1",
                                "display_name" => "fwr-test-1",
                                "sequence_number" => "2",
                                "sources_excluded" => false,
                                "destinations_excluded" => false,
                                "source_groups" =>  [
                                    "192.168.1.1",
                                    "192.168.1.2",
                                ],
                                "destination_groups" =>  [
                                    "192.168.1.3",
                                    "192.168.1.4",
                                ],
                                "services" =>  [
                                    "ANY",
                                ],
                                "service_entries" => [],
                                "profiles" =>  [
                                    "ANY",
                                ],
                                "logged" => true,
                                "scope" =>  [
                                    "/infra/tier-1s/rtr-test"
                                ],
                                "disabled" => false,
                                "notes" => "",
                                "direction" => "IN",
                                "tag" => "",
                                "ip_protocol" => "IPV4_IPV6",
                            ],
                        ],
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testPolicyWithPorts()
    {
        $firewallRule = $this->firewallPolicy()->firewallRules()->create([
            'id' => 'fwr-test-1',
            'name' => 'fwr-test-1',
            'sequence' => 2,
            'source' => '192.168.1.1, 192.168.1.2',
            'destination' => '192.168.1.3,192.168.1.4',
            'action' => 'REJECT',
            'direction' => 'IN',
            'enabled' => true,
        ]);

        $firewallRule->firewallRulePorts()->create([
            'id' => 'fwrp-test',
            'protocol' => 'TCP',
            'source' => '1, 2, 3 ,4-5',
            'destination' => '1, 2, 3 ,4-5',
        ]);

        $this->nsxServiceMock()->shouldReceive('patch')
            ->withArgs([
                '/policy/api/v1/infra/domains/default/gateway-policies/fwp-test',
                [
                    'json' => [
                        "id" => "fwp-test",
                        "display_name" => "fwp-test",
                        "description" => "name",
                        "sequence_number" => 10,
                        "rules" => [
                            [
                                "action" => "REJECT",
                                "resource_type" => "Rule",
                                "id" => "fwr-test-1",
                                "display_name" => "fwr-test-1",
                                "sequence_number" => "2",
                                "sources_excluded" => false,
                                "destinations_excluded" => false,
                                "source_groups" =>  [
                                    "192.168.1.1",
                                    "192.168.1.2",
                                ],
                                "destination_groups" =>  [
                                    "192.168.1.3",
                                    "192.168.1.4",
                                ],
                                "services" =>  [
                                    "ANY",
                                ],
                                "service_entries" => [
                                    [
                                        'id' => 'fwrp-test',
                                        'l4_protocol' => 'TCP',
                                        'resource_type' => 'L4PortSetServiceEntry',
                                        'source_ports' => ['1', '2', '3', '4-5'],
                                        'destination_ports' => ['1', '2', '3', '4-5']
                                    ]
                                ],
                                "profiles" =>  [
                                    "ANY",
                                ],
                                "logged" => true,
                                "scope" =>  [
                                    "/infra/tier-1s/rtr-test"
                                ],
                                "disabled" => false,
                                "notes" => "",
                                "direction" => "IN",
                                "tag" => "",
                                "ip_protocol" => "IPV4_IPV6",
                            ],
                        ],
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }
}
