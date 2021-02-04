<?php

namespace App\Jobs\Nsx\Nat;

use App\Jobs\Job;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    private $model;

    public function __construct(Nat $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $nic = collect((clone $this->model)->load([
            'destination',
            'translated',
            'source'
        ])->getRelations())->whereInstanceOf(Nic::class)->first();
        if (!$nic) {
            $message = 'Nat Deploy Failed. Could not find NIC for source, destination or translated';
            Log::error($message, ['id' => $this->model->id]);
            $this->model->setSyncFailureReason($message);
            $this->fail(new \Exception($message));
            return;
        }

        $router = $nic->network->router;
        if (!$router) {
            $message = 'Nat Deploy ' . $nic->id . ' : No Router found on the NIC';
            Log::error($message, ['id' => $this->model->id]);
            $this->model->setSyncFailureReason($message);
            $this->fail(new \Exception($message));
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

        try {
            $router->availabilityZone->nsxService()->patch(
                '/policy/api/v1/infra/tier-1s/' . $router->id . '/nat/USER/nat-rules/' . $this->model->id,
                ['json' => $json]
            );
        } catch (\Exception $exception) {
            if ($exception->hasResponse()) {
                Log::debug(get_class($this), json_decode($exception->getResponse()->getBody()->getContents(), true));
            }
            throw $exception;
        }

        $this->model->setSyncCompleted();
        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
