<?php

namespace App\Jobs\Nat;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Deploy the USER Nat Rule
     * @see https://185.197.63.88/policy/api_includes/method_PatchPolicyNatRule.html
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $nat = Nat::findOrFail($this->data['nat_id']);

        $nic = collect((clone $nat)->load(['destination', 'translated', 'source'])->getRelations())->whereInstanceOf(Nic::class)->first();
        if (!$nic) {
            $error = 'Nat Deploy Failed. Could not find NIC for source, destination or translated';
            Log::error($error, [
                'nat' => $nat,
            ]);
            $this->fail(new \Exception($error));
            return;
        }
        $this->data['nic_id'] = $nic->id;

        $router = $nic->network->router;
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
        $this->data['router_id'] = $router->id;

        Log::info('Nat Deploy ' . $this->data['nat_id'] . ' : Adding NAT ('.$nat->action.') Rule');

        $json = [
            'display_name' => $nat->id,
            'description' => $nat->id,
            'action' => $nat->action,
            'translated_network' => $nat->translated->ip_address,
            'enabled' => true,
            'logging' => false,
            'firewall_match' => 'MATCH_EXTERNAL_ADDRESS',
        ];
        $this->data['translated'] = $nat->translated->id . ' (' . $nat->translated->ip_address . ')';

        if (!empty($nat->destination)) {
            $json['destination_network'] = $nat->destination->ip_address;
            $this->data['destination'] = $nat->destination->id . ' (' . $nat->destination->ip_address . ')';
        }

        if (!empty($nat->source)) {
            $json['source_network'] = $nat->source->ip_address;
            $this->data['source'] = $nat->source->id . ' (' . $nat->source->ip_address . ')';
        }

        $router = $nic->network->router;

        $router->availabilityZone->nsxService()->patch(
            '/policy/api/v1/infra/tier-1s/' . $router->id . '/nat/USER/nat-rules/' . $nat->id,
            ['json' => $json]
        );

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
