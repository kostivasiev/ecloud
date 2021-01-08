<?php

namespace App\Jobs\Nsx\Nat;

use App\Jobs\Job;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    private $model;

    public function __construct(Nat $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['model' => $this->model]);

        // Load NIC from destination or translated
        $nic = collect(
            $this->model->load([
                'destination' => function ($query) {
                    $query->withTrashed();
                },
                'translated' => function ($query) {
                    $query->withTrashed();
                },
                'source' => function ($query) {
                    $query->withTrashed();
                }
            ])->getRelations()
        )
            ->whereInstanceOf(Nic::class)->first();

        if (!$nic) {
            $error = 'Failed. Could not find NIC for destination or translated';
            Log::error($error, [
                'nat' => $this->model,
            ]);
            $this->model->setSyncFailureReason($error);
            $this->fail(new \Exception($error));
            return;
        }

        $router = $nic->network->router;
        $router->availabilityZone->nsxService()->delete(
            'policy/api/v1/infra/tier-1s/' . $router->id . '/nat/USER/nat-rules/' . $this->model->id
        );

        Log::info(get_class($this) . ' : Finished', ['model' => $this->model]);
    }
}
