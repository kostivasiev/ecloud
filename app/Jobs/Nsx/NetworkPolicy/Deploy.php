<?php

namespace App\Jobs\Nsx\NetworkPolicy;

use App\Jobs\Job;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRulePort;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    private $model;

    public function __construct(NetworkPolicy $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $network = $this->model->network;
        $router = $network->router;
        $availabilityZone = $router->availabilityZone;

        /**
         * @see https://vdc-download.vmware.com/vmwb-repository/dcr-public/d9f0d8ce-b56e-45fa-9d32-ad9b95baa071/bd4b6353-6bbf-45ca-b7ef-3fa6c4905e94/api_includes/method_UpdateSecurityPolicyForDomain.html
         */
        $availabilityZone->nsxService()->patch(
            '/policy/api/v1/infra/domains/default/security-policies/' . $this->model->id,
            [
                'json' => [
                    'resource_type' => 'SecurityPolicy',
                    'id' => $this->model->id,
                    'display_name' => $this->model->id,
                    'sequence_number' => $this->model->sequence,
                    'category' => 'Application',
                    'stateful' => true,
                    'tcp_strict' => true,
                    'scope' => [
                        '/infra/domains/default/groups/' . $this->model->id,
                    ],
                    'rules' => $this->model->networkRules->map(function ($rule) use ($router) {
                        return [
                            'action' => $rule->action,
                            'resource_type' => 'Rule',
                            'id' => $rule->id,
                            'display_name' => $rule->id,
                            'sequence_number' => $rule->sequence,
                            'source_groups' => explode(',', $rule->source),
                            'destination_groups' => explode(',', $rule->destination),
                            'services' => [
                                'ANY'
                            ],
                            'service_entries' => $rule->networkRulePorts->map(function ($port) {
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
                                        explode(',', $port->source),
                                    'destination_ports' => $port->destination == 'ANY' ?
                                        [] :
                                        explode(',', $port->destination),
                                ];
                            })->toArray(),
                            'profiles' => [
                                'ANY'
                            ],
                            'logged' => false,
                            'scope' => [
                                'ANY'
                            ],
                            'ip_protocol' => 'IPV4_IPV6',
                        ];
                    })->toArray()
                ]
            ]
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function failed($exception)
    {
        $message = $exception->hasResponse() ? json_decode($exception->getResponse()->getBody()->getContents()) : $exception->getMessage();
        $this->model->setSyncFailureReason($message);
    }
}
