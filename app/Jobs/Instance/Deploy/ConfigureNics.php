<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class ConfigureNics extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info('Performing ConfigureNics for instance '. $this->data['instance_id']);

        $instance = Instance::findOrFail($this->data['instance_id']);
        $nsxService = $instance->availabilityZone->nsxService();
        $logMessage = 'ConfigureNics for instance ' . $instance->getKey() . ': ';

        /**
         * @see https://laravel.com/docs/8.x/queries#pessimistic-locking
         */
        $database = app('db')->connection('ecloud');
        $database->beginTransaction();

        $instanceNics = $instance->nics()
            ->whereNotNull('network_id')
            ->where('network_id', '!=', '')
            ->lockForUpdate()
            ->get();

        $nicsByNetwork = $instanceNics->groupBy('network_id');
        $nicsByNetwork->each(function ($nics, $networkId) use ($nsxService, $logMessage, $database) {
            $network = Network::findOrFail($networkId);
            $subnet = \IPLib\Range\Subnet::fromString($network->subnet);

            /**
             * Get DHCP static bindings to determine used IP addresses on the network
             * @see https://185.197.63.88/policy/api_includes/method_ListSegmentDhcpStaticBinding.html
             */
            try {
                $cursor = null;
                $assignedIpsNsx = collect();
                do {
                    $response = $nsxService->get('/policy/api/v1/infra/tier-1s/' . $network->router->getKey() . '/segments/' . $network->getKey() . '/dhcp-static-binding-configs?cursor=' . $cursor);
                    $response = json_decode($response->getBody()->getContents());
                    foreach ($response->results as $dhcpStaticBindingConfig) {
                        $assignedIpsNsx->push($dhcpStaticBindingConfig->ip_address);
                    }
                    $cursor = $response->cursor ?? null;
                } while (!empty($cursor));
            } catch (GuzzleException $exception) {
                $error = $logMessage . 'Failed: ' . $exception->getResponse()->getBody()->getContents();
                $database->rollback();
                Log::info($error);
                $this->fail(new \Exception($error));
                return;
            }

            $assignedIpsDb = $nics->pluck('ip_address')
                ->filter(function ($value) {
                    return !is_null($value);
                });

            foreach ($nics as $nic) {
                // We need to reserve the first 4 IPs of a range, and the last (for broadcast).
                $reserved = 3;
                $iterator = 0;

                $ip = $subnet->getStartAddress();
                while ($ip = $ip->getNextAddress()) {
                    $iterator++;
                    if ($iterator <= $reserved) {
                        continue;
                    }
                    if ($ip->toString() === $subnet->getEndAddress()->toString() || !$subnet->contains($ip)) {
                        $error = 'Insufficient available IP\'s in subnet to assign to NICs';
                        Log::info($error);
                        $database->rollback();
                        $this->fail(new \Exception($error));
                        return;
                    }

                    $checkIp = $ip->toString();

                    if ($assignedIpsDb->contains($checkIp)) {
                        continue;
                    }

                    //check NSX that the IP isn't in use.
                    if ($assignedIpsNsx->contains($checkIp)) {
                        continue;
                    }

                    $nic->ip_address = $checkIp;
                    $nic->save();
                    break;
                }
            }
        });

        $database->commit();

        //Create dhcp lease for the ip to the nic's mac address on NSX
        //https://185.197.63.88/policy/api_includes/method_CreateOrReplaceSegmentDhcpStaticBinding.html
        $instanceNics->each(function ($nic) use ($nsxService, $logMessage) {
            try {
                $nsxService->put(
                    '/policy/api/v1/infra/tier-1s/'.$nic->network->router->getKey().'/segments/'.$nic->network->getKey()
                    .'/dhcp-static-binding-configs/'.$nic->getKey(),
                    [
                        'json' => [
                            'resource_type' => 'DhcpV4StaticBindingConfig',
                            'mac_address' => $nic->mac_address,
                            'ip_address' => $nic->ip_address
                        ]
                    ]
                );
            } catch (GuzzleException $exception) {
                $error = $logMessage.'Failed: '.$exception->getResponse()
                        ->getBody()->getContents();
                Log::info($error);
                $this->fail(new \Exception($error));
                return;
            }
        });
    }
}
