<?php

namespace Tests\unit\Jobs\Nsx\NetworkPolicy;

use App\Jobs\Nsx\NetworkPolicy\Deploy;
use App\Models\V2\NetworkRule;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeployTest extends TestCase
{
    use DatabaseMigrations;

    public function testPolicyNoRulesDeploys()
    {
        $this->networkPolicy();

        $this->nsxServiceMock()->shouldReceive('patch')
            ->withArgs([
                '/policy/api/v1/infra/domains/default/security-policies/' . $this->networkPolicy()->id,
                [
                    'json' => [
                        'resource_type' => 'SecurityPolicy',
                        'id' => $this->networkPolicy()->id,
                        'display_name' => $this->networkPolicy()->id,
                        'category' => 'Application',
                        'stateful' => true,
                        'tcp_strict' => true,
                        'scope' => [
                            '/infra/domains/default/groups/' . $this->networkPolicy()->id,
                        ],
                        'rules' => []
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($this->networkPolicy()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testPolicyWithRulesDeploys()
    {
        Model::withoutEvents(function () {
            $networkRule = factory(NetworkRule::class)->make([
                'id' => 'nr-test-1',
                'name' => 'nr-test-1',
            ]);

            $networkRule->networkRulePorts()->create([
                'id' => 'nrp-test',
                'name' => 'nrp-test',
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '555',
            ]);

            $this->networkPolicy()->networkRules()->save($networkRule);
        });

        $this->nsxServiceMock()->shouldReceive('patch')
            ->withArgs([
                '/policy/api/v1/infra/domains/default/security-policies/np-test',
                [
                    'json' => [
                        'resource_type' => 'SecurityPolicy',
                        'id' => 'np-test',
                        'display_name' => 'np-test',
                        'category' => 'Application',
                        'stateful' => true,
                        'tcp_strict' => true,
                        'scope' => [
                            '/infra/domains/default/groups/np-test',
                        ],
                        'rules' => [
                            [
                                'action' => 'ALLOW',
                                'resource_type' => 'Rule',
                                'id' => 'nr-test-1',
                                'display_name' => 'nr-test-1',
                                'sequence_number' => 1,
                                'source_groups' => [
                                    '10.0.1.0/32'
                                ],
                                'destination_groups' => [
                                    '10.0.2.0/32'
                                ],
                                'services' => [
                                    'ANY'
                                ],
                                'service_entries' => [
                                    [
                                        'id' => 'nrp-test',
                                        'display_name' => 'nrp-test',
                                        'l4_protocol' => 'TCP',
                                        'resource_type' => 'L4PortSetServiceEntry',
                                        'source_ports' => [
                                            '443'
                                        ],
                                        'destination_ports' => [
                                            '555'
                                        ],
                                    ]
                                ],
                                'profiles' => [
                                    'ANY'
                                ],
                                'logged' => false,
                                'scope' => [
                                    'ANY'
                                ],
                                'ip_protocol' => 'IPV4_IPV6',
                            ]
                        ]
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($this->networkPolicy()));

        Event::assertNotDispatched(JobFailed::class);
    }
}