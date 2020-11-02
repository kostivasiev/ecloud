<?php

namespace App\Listeners\V2\Nic;

use App\Events\V2\Nic\Deleted;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class DeleteDhcpLease implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @see https://185.197.63.88/policy/api_includes/method_DeleteSegmentDhcpStaticBinding.html
     * @param Deleted $event
     * @return void
     */
    public function handle(Deleted $event)
    {
        $nic = $event->model;

        $logMessage = 'DeleteDhcpLease for NIC ' . $nic->getKey() . ': ';
        Log::info($logMessage . 'Started');

        $network = $nic->network;
        $router = $nic->network->router;
        $nsxService = $nic->instance->availabilityZone->nsxService();

        //Delete dhcp lease for the ip to the nic's mac address on NSX
        try {
            $response = $nsxService->delete(
                '/policy/api/v1/infra/tier-1s/' . $router->getKey() . '/segments/' . $network->getKey()
                . '/dhcp-static-binding-configs/' . $nic->getKey()
            );

            if ($response->getStatusCode() != 200) {
                $error =  $logMessage . 'Failed. Response was not 200';
                Log::error($error, ['response' => $response]);
                $this->fail(new \Exception($error));
                return;
            }
        } catch (GuzzleException $exception) {
            $error = ($exception->hasResponse()) ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
            Log::error($logMessage . 'Failed, ' . $error);
            $this->fail($exception);
            return;
        }

        Log::info('DHCP static binding deleted for ' . $nic->getKey() . ' (' . $nic->mac_address . ') with IP ' . $nic->ip_address);
    }
}
