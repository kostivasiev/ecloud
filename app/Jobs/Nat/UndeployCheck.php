<?php

namespace App\Jobs\Nat;

use App\Jobs\Job;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Models\V2\RouterScopable;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
{
    use Batchable, LoggableModelJob;

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
        $routerScopable = collect(
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
        )->whereInstanceOf(RouterScopable::class)->first();

        if (!$routerScopable) {
            $this->fail(new \Exception('Could not find router scopable resource for source, destination or translated'));
            return;
        }

        $router = $routerScopable->getRouter();
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
