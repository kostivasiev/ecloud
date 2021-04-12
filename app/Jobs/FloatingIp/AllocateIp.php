<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\AvailabilityZoneCapacity\UpdateFloatingIpCapacity;
use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use IPLib\Range\Subnet;
use UKFast\Admin\Networking\AdminClient;

class AllocateIp extends Job
{
    use Batchable;

    public FloatingIp $floatingIp;

    public function __construct(FloatingIp $floatingIp)
    {
        $this->floatingIp = $floatingIp;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->floatingIp->id]);

        if (!empty($this->floatingIp->ip_address)) {
            log::info("IP address already allocated for floating IP address");
            return;
        }


        $logMessage = 'Allocate external Ip to floating IP ' . $this->floatingIp->id . ': ';

        $datacentreSiteIds = $this->floatingIp->vpc->region->availabilityZones->pluck('datacentre_site_id')->unique();
        $networkingAdminClient = app()->make(AdminClient::class);

        $ipRanges = collect();
        $currentPage = 0;
        do {
            $currentPage++;
            $page = $networkingAdminClient->ipRanges()->getPage($currentPage, 15, [
                'auto_deploy_environment:eq' => 'ecloud nsx',
                'auto_deploy_datacentre_id:in' => implode(',', $datacentreSiteIds->toArray()),
                'type:eq' => 'External'
            ]);
            $ipRanges = $ipRanges->merge($page->getItems());
        } while ($currentPage < $page->totalPages());

        foreach ($ipRanges as $ipRange) {
            $subnet = Subnet::fromString(long2ip($ipRange->networkAddress) . '/' . $ipRange->cidr);
            if (empty($subnet)) {
                Log::error($logMessage . 'Failed to load subnet details from IP range ' . $ipRange->id);
                continue;
            }

            $iterator = 0;
            $ip = $subnet->getStartAddress(); //First IP / Network address (is reserved)

            $lock = Cache::lock("floating_ip_address." . $ipRange->networkAddress, 60);
            try {
                $lock->block(60);

                while ($ip = $ip->getNextAddress()) {
                    $iterator++;

                    if ($ip->toString() === $subnet->getEndAddress()->toString() || !$subnet->contains($ip)) {
                        Log::warning($logMessage . 'Insufficient available IPs in range ' . $ipRange->id);
                        continue 2;
                    }

                    $checkIp = $ip->toString();

                    //check no other FIPs have this IP address
                    if (FloatingIp::where('ip_address', $checkIp)
                            ->count() > 0) {
                        Log::debug('IP address "' . $checkIp . '" in use');
                        continue;
                    }

                    $this->floatingIp->ip_address = $checkIp;

                    Log::info($logMessage . 'Success. IP ' . $this->floatingIp->ip_address . ' was assigned.');

                    $this->floatingIp->vpc->region->availabilityZones->each(function ($availabilityZone) {
                        dispatch(new UpdateFloatingIpCapacity([
                            'availability_zone_id' => $availabilityZone->id
                        ]));
                    });
                    break 2;
                }

            } finally {
                $lock->release();
            }
        }

        if (empty($this->floatingIp->ip_address)) {
            $this->fail(new \Exception('Insufficient available external IPs to assign to floating IP resource ' . $this->floatingIp->id));
        }

        $this->floatingIp->saveQuietly();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->floatingIp->id]);
    }
}
