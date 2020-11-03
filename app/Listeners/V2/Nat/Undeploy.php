<?php

namespace App\Listeners\V2\Nat;

use App\Events\V2\Nat\Deleted;
use App\Models\V2\Nic;
use GuzzleHttp\Exception\GuzzleException;
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
        $nat = $event->model;

        $message = 'Nat ' . $nat->getKey() . ' Undeploy : ';
        Log::info($message . 'Started');

        // Load NIC from destination or translated
        $nic = collect($nat->load(['destination', 'translated'])->getRelations())->whereInstanceOf(Nic::class)->first();

        if (!$nic) {
            $error = $message . 'Failed. Could not find NIC for destination or translated';
            Log::error($error, [
                'nat' => $nat,
            ]);
            $this->fail(new \Exception($error));
            return;
        }

        $router = $nic->network->router;

        try {
            $response = $router->availabilityZone->nsxService()->delete(
                'policy/api/v1/infra/tier-1s/' . $router->getKey() . '/nat/USER/nat-rules/' . $nat->getKey()
            );

            if ($response->getStatusCode() !== 200) {
                $error = $message . 'Failed. Delete response was not 200';
                Log::error($error, ['response' => $response]);
                $this->fail(new \Exception($message));
                return;
            }
        } catch (GuzzleException $exception) {
            $error = ($exception->hasResponse()) ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
            Log::error('Failed to undeploy NAT ' . $nat->getKey() . ': ' . $error);
            $this->fail($exception);
            return;
        }

        Log::info($message . 'Success');
    }
}
