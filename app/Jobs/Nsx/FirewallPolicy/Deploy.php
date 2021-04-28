<?php

namespace App\Jobs\Nsx\FirewallPolicy;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRulePort;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    use Batchable;

    private $firewallPolicy;

    public function __construct(FirewallPolicy $firewallPolicy)
    {
        $this->firewallPolicy = $firewallPolicy;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->firewallPolicy->id]);

        $router = $this->firewallPolicy->router;
        $availabilityZone = $router->availabilityZone;

        /**
         * @see https://185.197.63.88/policy/api_includes/method_PatchGatewayPolicyForDomain.html
         */
        $availabilityZone->nsxService()->patch(
            'policy/api/v1/infra/domains/default/gateway-policies/' . $this->firewallPolicy->id,
            [
                'json' => [
                    'id' => $this->firewallPolicy->id,
                    'display_name' => $this->firewallPolicy->id,
                    'description' => $this->firewallPolicy->name,
                    'sequence_number' => $this->firewallPolicy->sequence,
                    'rules' => $this->firewallPolicy->firewallRules->map(function ($rule) use ($router) {
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
                                        explode(',', $port->source),
                                    'destination_ports' => $port->destination == 'ANY' ?
                                        [] :
                                        explode(',', $port->destination),
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

        Log::info(get_class($this) . ' : Finished', ['id' => $this->firewallPolicy->id]);
    }
}
