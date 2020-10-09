<?php

namespace App\Jobs;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use IPLib\Range\Subnet;

class ConfigureNic extends Job
{
    private $nic;

    const RETRY_ATTEMPTS = 10;

    const RETRY_DELAY = 10;

    public function __construct($nic)
    {
        $this->nic = $nic;
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
        Log::info('Starting ConfigureNic job for NIC ' . $this->nic->getKey());

        $logMessage = 'ConfigureNic ' . $this->nic->getKey() . ': ';

        $network = $this->nic->network;
        $router = $this->nic->network->router;
        $subnet = Subnet::fromString($network->subnet);
        $nsxService = $this->nic->instance->availabilityZone->nsxService();

        if (!$network->available) {
            if ($this->attempts() <= static::RETRY_ATTEMPTS) {
                $this->release(static::RETRY_DELAY);
                Log::info('Attempted to configure NIC (' . $this->nic->getKey() . ') on Network (' . $network->getKey() .
                    ') but Network was not available, will retry shortly');
                return;
            } else {
                $message = 'Timed out waiting for Network (' . $network->getKey() .
                    ') to become available for prior to NIC configuration';
                Log::error($message);
                $this->fail(new Exception($message));
                return;
            }
        }

        /**
         * Get DHCP static bindings to determine used IP addresses on the network
         * @see https://185.197.63.88/policy/api_includes/method_ListSegmentDhcpStaticBinding.html
         */
        try {
            $cursor = null;
            $assignedIpsNsx = collect();
            do {
                $response = $nsxService->get('/policy/api/v1/infra/tier-1s/' . $router->getKey() . '/segments/' . $network->getKey() . '/dhcp-static-binding-configs?cursor=' . $cursor);
                $response = json_decode($response->getBody()->getContents());
                foreach ($response->results as $dhcpStaticBindingConfig) {
                    $assignedIpsNsx->push($dhcpStaticBindingConfig->ip_address);
                }
                $cursor = $response->cursor ?? null;
            } while (!empty($cursor));
        } catch (GuzzleException $exception) {
            $this->fail(
                new Exception($logMessage . 'Failed: ' . $exception->getResponse()->getBody()->getContents())
            );
            return;
        }

        // We need to reserve the first 4 IPs of a range, and the last (for broadcast).
        $reserved = 3;
        $iterator = 0;

        $ip = $subnet->getStartAddress(); //First reserved IP
        while ($ip = $ip->getNextAddress()) {
            $iterator++;
            if ($iterator <= $reserved) {
                continue;
            }
            if ($ip->toString() === $subnet->getEndAddress()->toString() || !$subnet->contains($ip)) {
                $this->fail(new Exception('Insufficient available IP\'s in subnet to assign to NIC'));
                return;
            }

            $checkIp = $ip->toString();

            //check NSX that the IP isn't in use.
            if ($assignedIpsNsx->contains($checkIp)) {
                continue;
            }

            $this->nic->ip_address = $checkIp;

            try {
                $this->nic->save();
            } catch (Exception $exception) {
                if ($exception->getCode() == 23000) {
                    // Ip already assigned
                    Log::error('Failed to assign IP address ' . $checkIp . ' to NIC ' . $this->nic->getKey() . ': IP is already used.');
                    continue;
                }

                $this->fail(new Exception(
                    $logMessage . 'Failed: ' . $exception->getMessage()
                ));
                return;
            }

            Log::info('Ip Address ' . $this->nic->ip_address . ' assigned to ' . $this->nic->getKey());
            break;
        }


        //Create dhcp lease for the ip to the nic's mac address on NSX
        //https://185.197.63.88/policy/api_includes/method_CreateOrReplaceSegmentDhcpStaticBinding.html
        try {
            $nsxService->put(
                '/policy/api/v1/infra/tier-1s/' . $router->getKey() . '/segments/' . $network->getKey()
                . '/dhcp-static-binding-configs/' . $this->nic->getKey(),
                [
                    'json' => [
                        'resource_type' => 'DhcpV4StaticBindingConfig',
                        'mac_address' => $this->nic->mac_address,
                        'ip_address' => $this->nic->ip_address
                    ]
                ]
            );
        } catch (GuzzleException $exception) {
            $this->fail(new Exception(
                $logMessage . 'Failed: ' . $exception->getResponse()->getBody()->getContents()
            ));
            return;
        }
        Log::info('DHCP static binding created for ' . $this->nic->getKey() . ' (' . $this->nic->mac_address . ') with IP ' . $this->nic->ip_address);
    }
}
