<?php

namespace App\Listeners\V2\Nat;

use App\Events\V2\Nat\Deleted;
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
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $nat = $event->model;

        $message = 'Nat ' . $nat->getKey() . ' Undeploy : ';

        // Load NIC from destination or translated
        $nic = collect(
            $nat->load([
                'destination' => function ($query) {
                    $query->withTrashed();
                },
                'translated' => function ($query) {
                    $query->withTrashed();
                }
            ])->getRelations()
        )
            ->whereInstanceOf(Nic::class)->first();

        if (!$nic) {
            $error = $message . 'Failed. Could not find NIC for destination or translated';
            Log::error($error, [
                'nat' => $nat,
            ]);
            $this->fail(new \Exception($error));
            return;
        }

        $router = $nic->network->router;
        $router->availabilityZone->nsxService()->delete(
            'policy/api/v1/infra/tier-1s/' . $router->getKey() . '/nat/USER/nat-rules/' . $nat->getKey()
        );

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
