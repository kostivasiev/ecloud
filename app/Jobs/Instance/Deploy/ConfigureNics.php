<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use Illuminate\Support\Facades\Log;
use IPLib\Range\Subnet;

class ConfigureNics extends Job
{
    const RETRY_ATTEMPTS = 10;
    const RETRY_DELAY = 10;
    public $tries = 20;
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $network = Network::findOrFail($this->data['network_id']);
        if (!$network->available) {
            if ($this->attempts() <= static::RETRY_ATTEMPTS) {
                $this->release(static::RETRY_DELAY);
                Log::info('Attempted to configure NICs on Network (' . $network->id
                    . ') but Network was not available, will retry shortly');
                return;
            } else {
                $message = 'Timed out waiting for Network (' . $network->id .
                    ') to become available for prior to NIC configuration';
                $this->fail(new \Exception($message));
                return;
            }
        }

        $instance = Instance::findOrFail($this->data['instance_id']);

        $getInstanceResponse = $instance->availabilityZone->kingpinService()->get(
            '/api/v2/vpc/' . $instance->vpc->id . '/instance/' . $instance->id
        );

        $instanceData = json_decode($getInstanceResponse->getBody()->getContents());
        if (!$instanceData) {
            throw new \Exception('Deploy failed for ' . $instance->id . ', could not decode response');
        }

        Log::info(get_class($this) . ' : ' . count($instanceData->nics) . ' NIC\'s found');

        foreach ($instanceData->nics as $nicData) {
            $nic = app()->make(Nic::class);
            $nic->mac_address = $nicData->macAddress;
            $nic->instance_id = $instance->id;
            $nic->network_id = $network->id;
            $nic->save();
            Log::info(get_class($this) . ' : Created NIC resource ' . $nic->id);

            $router = $network->router;
            $subnet = Subnet::fromString($network->subnet);
            $nsxService = $nic->instance->availabilityZone->nsxService();

            /**
             * Get DHCP static bindings to determine used IP addresses on the network
             * @see https://185.197.63.88/policy/api_includes/method_ListSegmentDhcpStaticBinding.html
             */
            $cursor = null;
            $assignedIpsNsx = collect();
            do {
                $response = $nsxService->get('/policy/api/v1/infra/tier-1s/' . $router->id . '/segments/' . $network->id . '/dhcp-static-binding-configs?cursor=' . $cursor);
                $response = json_decode($response->getBody()->getContents());
                foreach ($response->results as $dhcpStaticBindingConfig) {
                    $assignedIpsNsx->push($dhcpStaticBindingConfig->ip_address);
                }
                $cursor = $response->cursor ?? null;
            } while (!empty($cursor));

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
                    $message = 'Insufficient available IP\'s in subnet to assign to NIC';
                    $nic->setSyncFailureReason($message);
                    $this->fail(new \Exception($message));
                    return;
                }

                $checkIp = $ip->toString();

                //check NSX that the IP isn't in use.
                if ($assignedIpsNsx->contains($checkIp)) {
                    continue;
                }

                $nic->ip_address = $checkIp;

                try {
                    // We're using withoutEvents here so we can prevent sync issues because we're
                    // using a database level constraint for atomic inserts of ip addresses .
                    Nic::withoutEvents(function () use ($nic) {
                        $nic->save();
                    });
                } catch (\Exception $exception) {
                    if ($exception->getCode() == 23000) {
                        // Ip already assigned
                        //Log::warning('Failed to assign IP address ' . $checkIp . ' to NIC ' . $nic->id . ': IP is already used.');
                        continue;
                    }

                    $this->fail(new \Exception(
                        'Configuring NIC ' . $nic->id . ': Failed: ' . $exception->getMessage()
                    ));
                    return;
                }

                Log::info('Ip Address ' . $nic->ip_address . ' assigned to ' . $nic->id);
                break;
            }


            /**
             * Create dhcp lease for the ip to the nic's mac address on NSX
             * @see https://185.197.63.88/policy/api_includes/method_CreateOrReplaceSegmentDhcpStaticBinding.html
             */
            try {
                $nsxService->put(
                    '/policy/api/v1/infra/tier-1s/' . $router->id . '/segments/' . $network->id
                    . '/dhcp-static-binding-configs/' . $nic->id,
                    [
                        'json' => [
                            'resource_type' => 'DhcpV4StaticBindingConfig',
                            'mac_address' => $nic->mac_address,
                            'ip_address' => $nic->ip_address
                        ]
                    ]
                );
            } catch (\Exception $exception) {
                if ($exception->hasResponse()) {
                    Log::info(get_class($this), json_decode($exception->getResponse()->getBody()->getContents(), true));
                }
                throw $exception;
            }

            $nic->setSyncCompleted();
            Log::info('DHCP static binding created for ' . $nic->id . ' (' . $nic->mac_address . ') with IP ' . $nic->ip_address);
        }

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
