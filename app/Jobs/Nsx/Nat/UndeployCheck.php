<?php

namespace App\Jobs\Nsx\Nat;

use App\Jobs\Job;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
{
    const RETRY_DELAY = 5;

    public $tries = 500;

    /** @var Nat */
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
        )->whereInstanceOf(Nic::class)->first();

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
        $response = $router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/' . $router->id . '/nat/USER/nat-rules/?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            if ($this->model->id === $result->id) {
                $this->release(static::RETRY_DELAY);
                Log::info(
                    'Waiting for ' . $this->model->id . ' being deleted, retrying in ' . static::RETRY_DELAY . ' seconds'
                );
                return;
            }
        }

        $this->model->setSyncCompleted();
        $this->model->syncDelete();

        Log::info(get_class($this) . ' : Finished', ['model' => $this->model]);
    }
}
