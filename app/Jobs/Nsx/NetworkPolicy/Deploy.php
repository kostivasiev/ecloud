<?php

namespace App\Jobs\Nsx\NetworkPolicy;

use App\Jobs\Job;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class Deploy extends Job
{
    use Batchable, LoggableModelJob;

    private NetworkPolicy $model;

    public function __construct(NetworkPolicy $networkPolicy)
    {
        $this->model = $networkPolicy;
    }

    public function handle()
    {
        $network = $this->model->network;
        $router = $network->router;
        $availabilityZone = $router->availabilityZone;
        $nsxService = $availabilityZone->nsxService();

        /**
         * @see https://vdc-download.vmware.com/vmwb-repository/dcr-public/d9f0d8ce-b56e-45fa-9d32-ad9b95baa071/bd4b6353-6bbf-45ca-b7ef-3fa6c4905e94/api_includes/method_UpdateSecurityPolicyForDomain.html
         * @see https://vdc-download.vmware.com/vmwb-repository/dcr-public/d9f0d8ce-b56e-45fa-9d32-ad9b95baa071/bd4b6353-6bbf-45ca-b7ef-3fa6c4905e94/api_includes/types_Rule.html
         */
        $availabilityZone->nsxService()->patch(
            '/policy/api/v1/infra/domains/default/security-policies/' . $this->model->id,
            [
                'json' => [
                    'resource_type' => 'SecurityPolicy',
                    'id' => $this->model->id,
                    'display_name' => $this->model->id,
                    'category' => 'Application',
                    'stateful' => true,
                    'tcp_strict' => true,
                    'scope' => [
                        '/infra/domains/default/groups/' . $this->model->id,
                    ],
                    'rules' => $this->model->networkRules->map(function ($rule) use ($router, $nsxService) {
                        return [
                            'action' => $rule->action,
                            'resource_type' => 'Rule',
                            'id' => $rule->id,
                            'display_name' => $rule->id,
                            'sequence_number' => $rule->sequence,
                            'source_groups' => $nsxService->csvToArray($rule->source),
                            'destination_groups' => $nsxService->csvToArray($rule->destination),
                            'services' => [
                                'ANY'
                            ],
                            'service_entries' => $rule->networkRulePorts->map(function ($port) use ($nsxService) {
                                if ($port->protocol == 'ICMPv4') {
                                    return [
                                        'id' => $port->getKey(),
                                        'display_name' => $port->id,
                                        'icmp_type' => NetworkRulePort::ICMP_MESSAGE_TYPE_ECHO_REQUEST,
                                        'resource_type' => 'ICMPTypeServiceEntry',
                                        'protocol' => 'ICMPv4',
                                    ];
                                }
                                return [
                                    'id' => $port->getKey(),
                                    'display_name' => $port->id,
                                    'l4_protocol' => $port->protocol,
                                    'resource_type' => 'L4PortSetServiceEntry',
                                    'source_ports' => $port->source == 'ANY' ?
                                        [] :
                                        $nsxService->csvToArray($port->source),
                                    'destination_ports' => $port->destination == 'ANY' ?
                                        [] :
                                        $nsxService->csvToArray($port->destination),
                                ];
                            })->toArray(),
                            'profiles' => [
                                'ANY'
                            ],
                            'direction' => $rule->direction ?? 'IN_OUT',
                            'logged' => false,
                            'scope' => [
                                '/infra/domains/default/groups/' . $this->model->id,
                            ],
                            'ip_protocol' => 'IPV4_IPV6',
                            'disabled' => !$rule->enabled,
                        ];
                    })->toArray(),
                    'tags' => [
                        [
                            'scope' => config('defaults.tag.scope'),
                            'tag' => $router->vpc->id
                        ]
                    ]
                ]
            ]
        );
    }
}
