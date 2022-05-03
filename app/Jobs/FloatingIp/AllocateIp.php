<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\AvailabilityZoneCapacity\UpdateFloatingIpCapacity;
use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Traits\V2\Jobs\FloatingIp\RdnsTrait;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use IPLib\Range\Subnet;
use UKFast\Admin\Networking\AdminClient;

class AllocateIp extends Job
{
    use Batchable, LoggableModelJob, RdnsTrait;

    public FloatingIp $model;

    public function __construct(FloatingIp $floatingIp)
    {
        $this->model = $floatingIp;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        if (!empty($this->model->ip_address)) {
            log::info("Floating IP already has an IP address allocated, skipping");
            return;
        }

        $networkingAdminClient = app()->make(AdminClient::class);

        $ipRanges = collect();
        $currentPage = 0;
        do {
            $currentPage++;
            $page = $networkingAdminClient->ipRanges()->getPage($currentPage, 15, [
                'auto_deploy_environment:eq' => 'ecloud nsx',
                'auto_deploy_datacentre_id:eq' => $this->model->availabilityZone->datacentre_site_id,
                'type:eq' => 'External'
            ]);
            $ipRanges = $ipRanges->merge($page->getItems());
        } while ($currentPage < $page->totalPages());

        $ipRanges = $ipRanges->shuffle();

        foreach ($ipRanges as $ipRange) {
            Log::debug('Checking for available IP addresses in range ' . $ipRange->id, ['id' => $this->model->id]);

            $subnet = Subnet::fromString(long2ip($ipRange->networkAddress) . '/' . $ipRange->cidr);
            if (empty($subnet)) {
                Log::error('Failed to load subnet details from IP range ' . $ipRange->id, ['id' => $this->model->id, 'networkAddress' => $ipRange->networkAddress, 'cidr' => $ipRange->cidr]);
                continue;
            }

            $lock = Cache::lock("floating_ip_address." . $ipRange->networkAddress, 60);
            try {
                $lock->block(60);

                $ipAddresses = collect();

                $ip = $subnet->getStartAddress();

                $start = true;
                while ($start || $ip = $ip->getNextAddress()) {
                    $start = false;
                    if ($ip == null || !$subnet->contains($ip)) {
                        break;
                    }

                    $ipAddresses->add($ip);
                }

                $ipAddresses = $ipAddresses->shuffle();

                foreach ($ipAddresses as $ipAddress) {
                    $checkIp = $ipAddress->toString();

                    //check no other FIPs have this IP address
                    if (FloatingIp::where('ip_address', $checkIp)
                            ->count() > 0) {
                        Log::debug('IP address "' . $checkIp . '" in use');
                        continue;
                    }

                    $rdns = $this->getRecords($checkIp);
                    $rdnsCount = count($rdns->getItems());

                    if ($rdnsCount === 1) {
                        $this->model->ip_address = $checkIp;
                        $this->model->saveQuietly();
                    } else {
                        continue;
                    }

                    Log::info('Success. IP ' . $this->model->ip_address . ' was assigned.', ['id' => $this->model->id]);

                    dispatch(new UpdateFloatingIpCapacity($this->model->availabilityZone));
                    break 2;
                }

                Log::warning('Insufficient available IPs in range ' . $ipRange->id, ['id' => $this->model->id]);
                continue;
            } finally {
                $lock->release();
            }
        }

        if (empty($this->model->ip_address)) {
            $this->fail(new \Exception('Insufficient available external IPs to assign to floating IP resource ' . $this->model->id));
            return;
        }
    }
}
