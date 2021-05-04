<?php

namespace App\Jobs\Nat;

use App\Jobs\Job;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    use Batchable, JobModel;
    
    private Nat $model;

    public function __construct(Nat $nat)
    {
        $this->model = $nat;
    }

    public function handle()
    {
        $nic = collect((clone $this->model)->load([
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

        Log::info('Nat Deploy ' . $this->model->id . ' : Adding NAT (' . $this->model->action . ') Rule');

        $json = [
            'display_name' => $this->model->id,
            'description' => $this->model->id,
            'action' => $this->model->action,
            'translated_network' => $this->model->translated->ip_address,
            'enabled' => true,
            'logging' => false,
            'firewall_match' => 'MATCH_EXTERNAL_ADDRESS',
        ];

        if (!empty($this->model->destination)) {
            $json['destination_network'] = $this->model->destination->ip_address;
        }

        if (!empty($this->model->source)) {
            $json['source_network'] = $this->model->source->ip_address;
        }

        $router->availabilityZone->nsxService()->patch(
            '/policy/api/v1/infra/tier-1s/' . $router->id . '/nat/USER/nat-rules/' . $this->model->id,
            ['json' => $json]
        );
    }
}
