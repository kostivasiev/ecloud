<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use IPLib\Range\Subnet;

class ConfigureNics extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $network = Network::findOrFail($this->model->deploy_data['network_id']);
        $getInstanceResponse = $this->model->availabilityZone->kingpinService()->get(
            '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id
        );

        $instanceData = json_decode($getInstanceResponse->getBody()->getContents());
        if (!$instanceData) {
            throw new \Exception('Deploy failed for ' . $this->model->id . ', could not decode response');
        }

        Log::info(get_class($this) . ' : ' . count($instanceData->nics) . ' NIC\'s found');

        foreach ($instanceData->nics as $nicData) {
            $nic = app()->make(Nic::class);
            $nic->mac_address = $nicData->macAddress;
            $nic->instance_id = $this->model->id;
            $nic->network_id = $network->id;

            $router = $network->router;
            $subnet = Subnet::fromString($network->subnet);
            $nsxService = $this->model->availabilityZone->nsxService();

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

            $lock = Cache::lock("ip_address." . $network->id, 60);
            try {
                $lock->block(60);

                while ($ip = $ip->getNextAddress()) {
                    $iterator++;
                    if ($iterator <= $reserved) {
                        continue;
                    }
                    if ($ip->toString() === $subnet->getEndAddress()->toString() || !$subnet->contains($ip)) {
                        $message = 'Insufficient available IP\'s in subnet to assign to NIC';
                        $this->fail(new \Exception($message));
                        return;
                    }

                    $checkIp = $ip->toString();

                    //check no other NICs have this IP address
                    if (Nic::where('network_id', $network->id)
                            ->where('ip_address', $checkIp)
                            ->count() > 0) {
                        Log::debug('IP address "' . $checkIp . '" in use');
                        continue;
                    }

                    //check NSX that the IP isn't in use.
                    if ($assignedIpsNsx->contains($checkIp)) {
                        Log::warning('IP address "' . $checkIp . '" in use within NSX');
                        continue;
                    }

                    $nic->ip_address = $checkIp;

                    Log::info('Ip Address ' . $nic->ip_address . ' assigned to ' . $nic->id);
                    break;
                }

                if (empty($nic->ip_address)) {
                    $this->fail(new \Exception("No available IP addresses found"));
                }

                $nic->save();
                Log::info(get_class($this) . ' : Created NIC resource ' . $nic->id);
            } finally {
                $lock->release();
            }
        }
    }
}
