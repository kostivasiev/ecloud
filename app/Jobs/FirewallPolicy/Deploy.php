<?php

namespace App\Jobs\FirewallPolicy;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $policy = FirewallPolicy::findOrFail($this->data['policy_id']);
        $router = $policy->router;
        $availabilityZone = $router->availabilityZone;

        $rules = [];
        $policy->firewallRules->each(function ($rule) use (&$rules, $router) {
            $rules[] = [
                'action' => $rule->action,
                'resource_type' => 'Rule',
                'id' => $rule->id,
                'display_name' => $rule->name,
                'sequence_number' => $rule->sequence,
                'sources_excluded' => false,
                'destinations_excluded' => false,
                'source_groups' => explode(',', $rule->source),
                'destination_groups' => explode(',', $rule->destination),
                'services' => [
                    'ANY'
                ],
                'profiles' => [
                    'ANY'
                ],
                'logged' => false,
                'scope' => [
                    '/infra/tier-1s/' . $router->id,
                ],
                'disabled' => !$rule->enabled,
                'notes' => '',
                'direction' => $rule->direction,
                'tag' => '',
                'ip_protocol' => 'IPV4_IPV6',
            ];
        });

        /**
         * @see https://185.197.63.88/policy/api_includes/method_PatchGatewayPolicyForDomain.html
         */
        $availabilityZone->nsxService()->patch(
            '/policy/api/v1/infra/domains/default/gateway-policies/' . $policy->id,
            [
                'json' => [
                    'id' => $policy->id,
                    'display_name' => $policy->name,
                    'description' => $policy->name,
                    'rules' => $rules,
                ]
            ]
        );

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
