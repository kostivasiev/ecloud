<?php

namespace App\Jobs\Nsx\FirewallPolicy;

use App\Jobs\TaskJob;
use App\Models\V2\FirewallRulePort;
use App\Services\V2\NsxService;
use Illuminate\Support\Str;

class Deploy extends TaskJob
{
    public function handle()
    {
        $firewallPolicy = $this->task->resource;
        $router = $firewallPolicy->router;
        $availabilityZone = $router->availabilityZone;

        /**
         * @see https://185.197.63.88/policy/api_includes/method_PatchGatewayPolicyForDomain.html
         */
        $availabilityZone->nsxService()->patch(
            sprintf(NsxService::PATCH_GATEWAY_POLICY, $firewallPolicy->id),
            [
                'json' => [
                    'id' => $firewallPolicy->id,
                    'display_name' => $firewallPolicy->id,
                    'description' => $firewallPolicy->name,
                    'sequence_number' => $firewallPolicy->sequence,
                    'rules' => $firewallPolicy->firewallRules->map(function ($rule) use ($router) {
                        return [
                            'action' => $rule->action,
                            'resource_type' => 'Rule',
                            'id' => $rule->id,
                            'display_name' => $rule->id,
                            'sequence_number' => $rule->sequence,
                            'sources_excluded' => false,
                            'destinations_excluded' => false,
                            'source_groups' => explode(',', $rule->source),
                            'destination_groups' => explode(',', $rule->destination),
                            'services' => [
                                'ANY'
                            ],
                            'service_entries' => $rule->firewallRulePorts->map(function ($port) {
                                if ($port->protocol == 'ICMPv4') {
                                    return [
                                        'id' => $port->id,
                                        'icmp_type' => FirewallRulePort::ICMP_MESSAGE_TYPE_ECHO_REQUEST,
                                        'resource_type' => 'ICMPTypeServiceEntry',
                                        'protocol' => 'ICMPv4',
                                    ];
                                }
                                return [
                                    'id' => $port->id,
                                    'l4_protocol' => $port->protocol,
                                    'resource_type' => 'L4PortSetServiceEntry',
                                    'source_ports' => $port->source == 'ANY' ?
                                        [] :
                                        explode(',', trim(Str::replace('.', ',', $port->source))),
                                    'destination_ports' => $port->destination == 'ANY' ?
                                        [] :
                                        explode(',', trim(Str::replace('.', ',', $port->destination))),
                                ];
                            })->toArray(),
                            'profiles' => [
                                'ANY'
                            ],
                            'logged' => true,
                            'scope' => [
                                '/infra/tier-1s/' . $router->id,
                            ],
                            'disabled' => !$rule->enabled,
                            'notes' => '',
                            'direction' => $rule->direction,
                            'tag' => '',
                            'ip_protocol' => 'IPV4_IPV6'
                        ];
                    })->toArray()
                ]
            ]
        );
    }
}
