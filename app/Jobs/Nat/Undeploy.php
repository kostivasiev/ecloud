<?php

namespace App\Jobs\Nat;

use App\Jobs\Job;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    use Batchable;
    
    private Nat $nat;

    public function __construct(Nat $nat)
    {
        $this->nat = $nat;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->nat->id]);

        // Load NIC from destination or translated
        $nic = collect(
            $this->nat->load([
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
                '/policy/api/v1/infra/tier-1s/' . $router->id . '/nat/USER/nat-rules/' . $this->nat->id
            );
        } catch (ClientException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '404') {
                Log::info("NAT already removed, skipping");
                return;
            }

            throw $e;
        }

        $router->availabilityZone->nsxService()->delete(
            '/policy/api/v1/infra/tier-1s/' . $router->id . '/nat/USER/nat-rules/' . $this->nat->id
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->nat->id]);
    }
}
