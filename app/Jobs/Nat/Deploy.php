<?php

namespace App\Jobs\Nat;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
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

        $nat = Nat::findOrFail($this->data['nat_id']);

        // Instance lookup
        $instanceId = collect($nat->destination->getAttributes())->has('instance_id') ?
            $nat->destination->instance_id : null;
        $instanceId = collect($nat->translated->getAttributes())->has('instance_id') ?
            $nat->translated->instance_id : $instanceId;
        if (!$instanceId) {
            $message = 'Nat Deploy ' . $this->data['nat_id'] . ' : No instance found for the destination or translated';
            Log::error($message, [
                'data' => $this->data,
                'nat' => $nat,
            ]);
            $this->fail(new \Exception($message));
            return;
        }
        $instance = Instance::findOrFail($instanceId);

        // NIC lookup
        $nic = $nat->destination instanceof Nic ? $nat->destination : null;
        $nic = $nat->translated instanceof Nic ? $nat->translated : $nic;
        if (!$nic) {
            $message = 'Nat Deploy ' . $this->data['nat_id'] . ' : No NIC found for the destination or translated';
            Log::error($message, [
                'data' => $this->data,
                'nat' => $nat,
            ]);
            $this->fail(new \Exception($message));
            return;
        }

        // Router lookup
        $router = $nic->network->router;
        $this->data['router_id'] = $router->id;
        if (!$router) {
            $message = 'Nat Deploy ' . $this->data['nat_id'] . ' : No Router found on the NIC';
            Log::error($message, [
                'data' => $this->data,
                'nat' => $nat,
                'nic' => $nic,
            ]);
            $this->fail(new \Exception($message));
            return;
        }

        Log::info('Nat Deploy ' . $this->data['nat_id'] . ' : Adding NAT Rule');
        /**
         * Deploy the USER Nat Rule
         * @see https://185.197.63.88/policy/api_includes/method_PatchPolicyNatRule.html
         */
        $instance->availabilityZone->nsxService()->patch(
            '/policy/api/v1/infra/tier-1s/' . $router->id . '/nat/USER/nat-rules/' . $nat->id,
            [
                'json' => [
                    'display_name' => $nat->id,
                    'description' => $nat->id,
                    'action' => 'DNAT',
                    'destination_network' => $nat->destination->ip_address,
                    'translated_network' => $nat->translated->ip_address,
                    'enabled' => true,
                    'logging' => false,
                    'firewall_match' => 'MATCH_EXTERNAL_ADDRESS',
                ]
            ]
        );

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
