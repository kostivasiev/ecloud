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
        Log::info('Nat Deploy ' . $this->data['nat_id'] . ' : Started');
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
        }

        // Router lookup
        $router = $nic->network->router;
        if (!$router) {
            $message = 'Nat Deploy ' . $this->data['nat_id'] . ' : No Router found on the NIC';
            Log::error($message, [
                'data' => $this->data,
                'nat' => $nat,
                'nic' => $nic,
            ]);
            $this->fail(new \Exception($message));
        }

        $oldRuleId = $this->data['original_destination_id'] . '-to-' . $this->data['original_translated_id'];
        if ($oldRuleId !== '-to-') {
            Log::info('Nat Deploy ' . $this->data['nat_id'] . ' : Deleting ' . $oldRuleId . ' NAT Rule');
            try {
                $response = $instance->availabilityZone->nsxService()->delete('/policy/api/v1/infra/tier-1s/' . $router->id . '/nat/' . $nat->id . '/nat-rules/' . $nat->rule_id);
                // TODO :- Check the response is as expected, otherwise fail
            } catch (\Exception $exception) {
                $message = 'Nat Deploy ' . $this->data['nat_id'] . ' : Failed to delete the old NAT rule ' . $oldRuleId;
                Log::error($message, ['exception' => $exception]);
                $this->fail(new \Exception($message));
            }
        }

        Log::info('Nat Deploy ' . $this->data['nat_id'] . ' : Adding ' . $nat->rule_id . ' NAT Rule');
        try {
            $response = $instance->availabilityZone->nsxService()->patch('/policy/api/v1/infra/tier-1s/' . $router->id . '/nat/' . $nat->id . '/nat-rules/' . $nat->rule_id,
                [
                    'json' => [
                        'display_name' => $nat->id,
                        'description' => $nat->rule_id,
                        'action' => 'DNAT',
                        'destination_network' => $nat->destination->ip_address,
                        'translated_network' => $nat->translated->ip_address,
                        'translated_ports' => '*',
                        'enabled' => true,
                        'logging' => false,
                        'firewall_match' => 'MATCH_EXTERNAL_ADDRESS',
                        'scope' => ['infra/tier-0s/tier-0-vmc/interfaces/internet'],
                    ]
                ]);
            // TODO :- Check the response is as expected, otherwise fail
        } catch (\Exception $exception) {
            $message = 'Nat Deploy ' . $this->data['nat_id'] . ' : Failed to add new NAT rule ' . $nat->rule_id;
            Log::error($message, ['exception' => $exception]);
            $this->fail(new \Exception($message));
        }

        Log::info('Nat Deploy ' . $this->data['nat_id'] . ' : Finished');
    }
}
