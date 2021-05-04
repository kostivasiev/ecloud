<?php

namespace App\Jobs\Nat;

use App\Jobs\Job;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Traits\V2\JobModel;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    use Batchable, JobModel;
    
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
        )
            ->whereInstanceOf(Nic::class)->first();

        if (!$nic) {
            $this->fail(new \Exception('Could not find NIC for destination or translated'));
            return;
        }

        $router = $nic->network->router;

        try {
            $router->availabilityZone->nsxService()->get(
                '/policy/api/v1/infra/tier-1s/' . $router->id . '/nat/USER/nat-rules/' . $this->model->id
            );
        } catch (ClientException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '404') {
                Log::info("NAT already removed, skipping");
                return;
            }

            throw $e;
        }

        $router->availabilityZone->nsxService()->delete(
            '/policy/api/v1/infra/tier-1s/' . $router->id . '/nat/USER/nat-rules/' . $this->model->id
        );
    }
}
