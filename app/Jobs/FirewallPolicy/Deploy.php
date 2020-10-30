<?php

namespace App\Jobs\FirewallPolicy;

use App\Jobs\TaskJob;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\Task;
use Illuminate\Support\Facades\Log;

class Deploy extends TaskJob
{
    private $data;

    public function __construct(Task $task, $data)
    {
        parent::__construct($task);
        $this->data = $data;
    }

    public function handle()
    {
        Log::info('Starting Deploy Firewall Policy ' . $this->data['policy_id']);
        $policy = FirewallPolicy::findOrFail($this->data['policy_id']);
        $router = $policy->router;
        $availabilityZone = $router->availabilityZone;

        $rules = [];
        $policy->rules->each(function ($rule) use ($rules) {
            /**
            "name": "Rule Test 1",
            "router_id": "rtr-259e5f91",
            "firewall_policy_id": "fwp-55c9cb69",
            "source": "100.64.0.0/16",
            "destination": "100.64.0.0-100.64.0.32",
            "action": "ALLOW",
            "direction": "IN",
            "enabled": true
             */
            $rules[] = [
                'action' => $rule->action,
                'resource_type' => 'Rule',
                'id' => $rule->id,
                'display_name' => $rule->name,




                /////////////////////////////////////////////////////
                ///
                /// GOT TO HERE AND JUMPED ON TO #500 SINCE FIREWALL
                /// RULES DONT HAVE `sequence`
                ///
                ////////////////////////////////////////////////////


                'sequence_number' => 2,
                'sources_excluded' => false,
                'destinations_excluded' => false,
                'source_groups' => [
                    '86.21.40.165'
                ],
                'destination_groups' => [
                    '46.37.164.1'
                ],
                'services' => [
                    'ANY'
                ],
                'profiles' => [
                    'ANY'
                ],
                'logged' => false,
                'scope' => [
                    '/infra/tier-1s/Reference_T1'
                ],
                'disabled' => false,
                'notes' => '',
                'direction' => 'IN',
                'tag' => '',
                'ip_protocol' => 'IPV4_IPV6',
            ];
        });

        try {
            /**
             * @see https://185.197.63.88/policy/api_includes/method_PatchGatewayPolicyForDomain.html
             */
            $response = $availabilityZone->nsxService()->patch(
                '/policy/api/v1/infra/domains/default/gateway-policies/' . $policy->id,
                [
                    'json' => [
                        'rules' => $rules,
                    ]
                ]
            );
            if ($response->getStatusCode() !== 200) {
                $message = 'Deploy Firewall Policy ' . $this->data['policy_id'] . ' : Failed to add new Policy';
                Log::error($message, ['response' => $response]);
                $this->fail(new \Exception($message));
            }
        } catch (\Exception $exception) {
            $message = 'Deploy Firewall Policy ' . $this->data['policy_id'] . ' : Exception while adding new Policy';
            Log::error($message, ['exception' => $exception]);
            $this->fail(new \Exception($message));
        }

        Log::info('Deploy Firewall Policy ' . $this->data['policy_id'] . ' : Finished');
    }
}
