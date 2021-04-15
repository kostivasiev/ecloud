<?php

namespace App\Jobs\Nat;

use App\Jobs\Job;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    use Batchable;
    
    private Nat $nat;

    public function __construct(Nat $nat)
    {
        $this->nat = $nat;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->nat->id]);

        $nic = collect((clone $this->nat)->load([
            'destination',
            'translated',
            'source'
        ])->getRelations())->whereInstanceOf(Nic::class)->first();
        if (!$nic) {
            $this->fail(new \Exception('Nat Deploy Failed. Could not find NIC for source, destination or translated'));
            return;
        }

        $router = $nic->network->router;
        if (!$router) {
            $this->fail(new \Exception('Nat Deploy ' . $nic->id . ' : No Router found for NIC network'));
            return;
        }

        Log::info('Nat Deploy ' . $this->nat->id . ' : Adding NAT (' . $this->nat->action . ') Rule');

        $json = [
            'display_name' => $this->nat->id,
            'description' => $this->nat->id,
            'action' => $this->nat->action,
            'translated_network' => $this->nat->translated->ip_address,
            'enabled' => true,
            'logging' => false,
            'firewall_match' => 'MATCH_EXTERNAL_ADDRESS',
        ];

        if (!empty($this->nat->destination)) {
            $json['destination_network'] = $this->nat->destination->ip_address;
        }

        if (!empty($this->nat->source)) {
            $json['source_network'] = $this->nat->source->ip_address;
        }

        $router->availabilityZone->nsxService()->patch(
            '/policy/api/v1/infra/tier-1s/' . $router->id . '/nat/USER/nat-rules/' . $this->nat->id,
            ['json' => $json]
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->nat->id]);
    }
}
