<?php

namespace App\Jobs\Nsx\FirewallPolicy;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRulePort;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    private $model;

    public function __construct(FirewallPolicy $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['model' => $this->model]);

        $router = $this->model->router;
        $availabilityZone = $router->availabilityZone;

        /**
         * @see https://185.197.63.88/policy/api_includes/method_PatchGatewayPolicyForDomain.html
         */
        $availabilityZone->nsxService()->patch(
            '/policy/api/v1/infra/domains/default/gateway-policies/' . $this->model->id,
            [
                'json' => [
                    'id' => $this->model->id,
                    'display_name' => $this->model->name,
                    'description' => $this->model->name,
                    'sequence_number' => $this->model->sequence,
                    'rules' => $this->model->firewallRules->map(function ($rule) use ($router) {
                        return [
                            'action' => $rule->action,
                            'resource_type' => 'Rule',
                            'id' => $rule->id,
                            'display_name' => $rule->name,
                            'sequence_number' => $rule->sequence,
                            'sources_excluded' => false,
                            'destinations_excluded' => false,
                            'source_groups' => empty($rule->source) ? ['ANY'] : explode(',', $rule->source),
                            'destination_groups' => empty($rule->source) ? ['ANY'] : explode(',', $rule->destination),
                            'services' => [
                                'ANY'
                            ],
                            'service_entries' => $rule->firewallRulePorts->map(function ($port) {
                                if ($port->protocol == 'ICMPv4') {
                                    return [
                                        'id' => $port->getKey(),
                                        'icmp_type' => FirewallRulePort::ICMP_MESSAGE_TYPE_ECHO_REQUEST,
                                        'resource_type' => 'ICMPTypeServiceEntry',
                                        'protocol' => 'ICMPv4',
                                    ];
                                }
                                return [
                                    'id' => $port->getKey(),
                                    'l4_protocol' => $port->protocol,
                                    'resource_type' => 'L4PortSetServiceEntry',
                                    'source_ports' => empty($port->source) ? [] : explode(',', $port->source),
                                    'destination_ports' => empty($port->destination) ? [] : explode(',',
                                        $port->destination),
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

        Log::info(get_class($this) . ' : Finished', ['model' => $this->model]);
    }
}
