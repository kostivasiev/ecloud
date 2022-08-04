<?php

namespace App\Jobs\Nat;

use App\Jobs\Job;
use App\Models\V2\Nat;
use App\Models\V2\RouterScopable;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use IPLib\Factory;

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
            $this->fail(new \Exception('Could not find router scopable resource for source, destination or translated'));
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
            'tags' => [
                [
                    'scope' => config('defaults.tag.scope'),
                    'tag' => $router->vpc->id
                ]
            ]
        ];

        if ($this->model->action !== Nat::ACTION_NOSNAT) {
            $json['firewall_match'] = 'MATCH_EXTERNAL_ADDRESS';
        }

        if (!empty($this->model->sequence)) {
            $json['sequence_number'] = $this->model->sequence;
        }

        if (!empty($this->model->destination)) {
            $destination = Factory::rangeFromString($this->model->destination->getIPAddress());
            $json['destination_network'] = $destination->toString();
        }

        if (!empty($this->model->source)) {
            $source = Factory::rangeFromString($this->model->source->getIPAddress());
            $json['source_network'] = $source->toString();
        }

        if (!empty($this->model->translated)) {
            $translated = Factory::rangeFromString($this->model->translated->getIPAddress());
            $json['translated_network'] = $translated->toString();
        }

        $router->availabilityZone->nsxService()->patch(
            '/policy/api/v1/infra/tier-1s/' . $router->id . '/nat/USER/nat-rules/' . $this->model->id,
            ['json' => $json]
        );
    }
}
