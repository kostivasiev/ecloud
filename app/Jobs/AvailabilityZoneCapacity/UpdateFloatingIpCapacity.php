<?php

namespace App\Jobs\AvailabilityZoneCapacity;

use App\Jobs\Job;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\AvailabilityZoneCapacity;
use App\Models\V2\FloatingIp;
use Illuminate\Support\Facades\Log;
use IPLib\Range\Subnet;
use UKFast\Admin\Networking\AdminClient;

class UpdateFloatingIpCapacity extends Job
{
    private AvailabilityZone $availabilityZone;

    public function __construct(AvailabilityZone $availabilityZone)
    {
        $this->availabilityZone = $availabilityZone;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->availabilityZone->id]);

        $availabilityZoneCapacity = AvailabilityZoneCapacity::where('availability_zone_id', $this->availabilityZone->id)
            ->where('type', 'floating_ip')->first();

        if (empty($availabilityZoneCapacity)) {
            Log::info('No \'floating_ip\' capacity record found for availability zone ' . $this->availabilityZone->id . ', Skipping.');
            return;
        }

        $networkingAdminClient = app()->make(AdminClient::class);

        $ipRanges = collect();
        $currentPage = 0;
        do {
            $currentPage++;
            $page = $networkingAdminClient->ipRanges()->getPage($currentPage, 15, [
                'auto_deploy_environment:eq' => 'ecloud nsx',
                'auto_deploy_datacentre_id:eq' => $this->availabilityZone->datacentre_site_id,
                'type:eq' => 'External'
            ]);
            $ipRanges = $ipRanges->merge($page->getItems());
        } while ($currentPage < $page->totalPages());

        // As the ip ranges are loaded from all az's for the region we need to load all fips associated with the region
        $floatingIps = FloatingIp::withRegion($this->availabilityZone->region->id)->whereNotNull('ip_address');

        $runningTotal = [
            'total' => 0,
            'used' => 0
        ];

        $data = [];

        foreach ($ipRanges as $ipRange) {
            $subnet = Subnet::fromString(long2ip($ipRange->networkAddress) . '/' . $ipRange->cidr);
            if (empty($subnet)) {
                Log::error('Failed to load subnet details from IP range ' . $ipRange->id, ['networkAddress' => $ipRange->networkAddress, 'cidr' => $ipRange->cidr]);
                continue;
            }

            // Total number of usable IP's in the range based on netmask
            $rangeTotal = $rangeAvailable = (1 << (32 - $ipRange->cidr)) - 2;

            $runningTotal['total'] += $rangeTotal;

            $floatingIps->each(function ($floatingIp) use ($subnet, &$rangeAvailable, &$runningTotal) {
                $ip = \IPLib\Address\IPv4::fromString($floatingIp->ip_address);
                if ($subnet->contains($ip)) {
                    $rangeAvailable--;
                    $runningTotal['used']++;
                }
            });

            $data['ip-ranges'][$ipRange->id]['total_ip_count'] = $rangeTotal;
            $data['ip-ranges'][$ipRange->id]['available_ip_count'] = $rangeAvailable;
        }

        $percentUsed = round(($runningTotal['used'] / $runningTotal['total']) * 100, 2);

        $data['running_totals'] = $runningTotal;
        $data['running_totals']['percent_used'] = $percentUsed;

        if ($availabilityZoneCapacity->current != $percentUsed) {
            $availabilityZoneCapacity->current = $percentUsed;
            $availabilityZoneCapacity->save();
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->availabilityZone->id, 'data' => $data]);
    }
}
