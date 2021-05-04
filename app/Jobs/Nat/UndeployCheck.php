<?php

namespace App\Jobs\Nat;

use App\Jobs\Job;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
{
    use Batchable, JobModel;

    // Wait up to 30 minutes
    public $tries = 360;
    public $backoff = 5;
    
    private Nat $model;

    public function __construct(Nat $nat)
    {
        $this->model = $nat;
    }

    public function handle()
    {
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
            $this->fail(new \Exception('Could not find NIC for destination or translated'));
            return;
        }

        $router = $nic->network->router;
        $response = $router->availabilityZone->nsxService()->get(
            '/policy/api/v1/infra/tier-1s/' . $router->id . '/nat/USER/nat-rules/?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            if ($this->model->id === $result->id) {
                Log::info(
                    'Waiting for ' . $this->model->id . ' being deleted, retrying in ' . $this->backoff . ' seconds'
                );
                $this->release($this->backoff);
                return;
            }
        }
    }
}
