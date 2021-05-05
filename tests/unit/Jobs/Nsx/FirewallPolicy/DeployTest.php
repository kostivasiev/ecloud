<?php

namespace Tests\unit\Jobs\Nsx\FirewallPolicy;

use App\Jobs\Nsx\FirewallPolicy\Deploy;
use App\Models\V2\FirewallPolicy;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeployTest extends TestCase
{
    protected FirewallPolicy $firewallPolicy;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testPolicyNoRulesDeploys()
    {
        $this->firewallPolicy = Model::withoutEvents(function () {
            return factory(FirewallPolicy::class)->create([
                'id' => 'fwp-test',
                'router_id' => $this->router()->id,
            ]);
        });

        $this->nsxServiceMock()->shouldReceive('patch')
            ->withArgs([
                'policy/api/v1/infra/domains/default/gateway-policies/fwp-test',
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

        dispatch(new Deploy($this->firewallPolicy));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testPolicyWithRulesDeploys()
    {
        Model::withoutEvents(function () {
            $this->firewallPolicy = factory(FirewallPolicy::class)->create([
                'id' => 'fwp-test',
                'router_id' => $this->router()->id,
            ]);

            $this->firewallPolicy->firewallRules()->create([
                'id' => 'fwr-test-1',
                'name' => 'fwr-test-1',
                'sequence' => 2,
                'source' => '192.168.1.1',
                'destination' => '192.168.1.2',
                'action' => 'REJECT',
                'direction' => 'IN',
                'enabled' => true,
            ]);
        });

        $this->nsxServiceMock()->shouldReceive('patch')
            ->withArgs([
                'policy/api/v1/infra/domains/default/gateway-policies/fwp-test',
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
                                ],
                                "destination_groups" =>  [
                                    "192.168.1.2",
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

        dispatch(new Deploy($this->firewallPolicy));

        Event::assertNotDispatched(JobFailed::class);
    }
}
