<?php

namespace App\Jobs\Nat;

use App\Jobs\Job;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Models\V2\RouterScopable;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    use Batchable, LoggableModelJob;
    
    private Nat $model;

    public function __construct(Nat $nat)
    {
        $this->model = $nat;
    }

    public function handle()
    {
        $routerScopable = collect((clone $this->model)->load([
            'destination',
            'translated',
            'source'
        ])->getRelations())->whereInstanceOf(RouterScopable::class)->first();
        if (!$routerScopable) {
            $this->fail(new \Exception('Nat Deploy Failed. Could not find router scopable resource for source, destination or translated'));
            return;
        }

        $router = $routerScopable->getRouter();
        if (!$router) {
            $this->fail(new \Exception('Nat Deploy ' . $this->model->id . ' : No Router found for resource'));
            return;
        }

        Log::info('Nat Deploy ' . $this->model->id . ' : Adding NAT (' . $this->model->action . ') Rule');

        $json = [
            'display_name' => $this->model->id,
            'description' => $this->model->id,
            'action' => $this->model->action,
            'enabled' => true,
            'logging' => false,
        ];

        if ($this->model->action !== Nat::ACTION_NOSNAT) {
            $json['firewall_match'] = 'MATCH_EXTERNAL_ADDRESS';
        }

        if (!empty($this->model->sequence)) {
            $json['sequence_number'] = $this->model->sequence;
        }

        if (!empty($this->model->destination)) {
            $json['destination_network'] = $this->model->destination->getIPAddress();
        }

        if (!empty($this->model->source)) {
            $json['source_network'] = $this->model->source->getIPAddress();
        }

        if (!empty($this->model->translated)) {
            $json['translated_network'] = $this->model->translated->getIPAddress();
        }

        $router->availabilityZone->nsxService()->patch(
            '/policy/api/v1/infra/tier-1s/' . $router->id . '/nat/USER/nat-rules/' . $this->model->id,
            ['json' => $json]
        );
    }
}
