<?php

namespace App\Jobs\Nat;

use App\Jobs\Job;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Models\V2\RouterScopable;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    use Batchable, LoggableModelJob;
    
    private Nat $model;

    public function __construct(Nat $nat)
    {
        $this->model = $nat;
    }

    public function handle()
    {
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
        )
            ->whereInstanceOf(RouterScopable::class)->first();

        if (!$routerScopable) {
            $this->fail(new \Exception('Could not find router scopable resource for source, destination or translated'));
            return;
        }

        $router = $routerScopable->getRouter();

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
