<?php

namespace App\Listeners\V2\Nat;

use App\Events\V2\Nat\Deleted;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nic;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class Undeploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param Deleted $event
     * @return void
     * @throws \Exception
     */
    public function handle(Deleted $event)
    {
        $nat = $event->nat;

        // Load NIC from destination or translated
        $nic = collect($nat->getRelations())->whereInstanceOf(Nic::class)->first();

        if (!$nic) {
            exit('unsupported NAT translated type');
        }

        try {
            $nic->network->router->availabilityZone->nsxService()->delete(
                'policy/api/v1/infra/tier-1s/' . $nic->network->router->getKey() . '/nat/USER/nat-rules/' . $nat->getKey()
            );
        } catch (GuzzleException $exception) {
            $error = ($exception->hasResponse()) ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
            Log::error('Failed to undeploy NAT ' . $nat->getKey() . ': ' . $error);
            $this->fail($exception);
            return;
        }


    }
}
