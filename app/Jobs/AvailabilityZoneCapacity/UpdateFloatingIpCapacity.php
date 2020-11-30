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
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $availabilityZone = AvailabilityZone::findOrFail($this->data['availability_zone_id']);

        $availabilityZoneCapacity = AvailabilityZoneCapacity::where('availability_zone_id', $availabilityZone->getKey())
            ->where('type', 'floating_ip')->first();

        if (empty($availabilityZoneCapacity)) {
            Log::info('No \'floating_ip\' capacity record found for availability zone ' . $availabilityZone->getKey() . ', Skipping.');
            return;
        }

        $networkingAdminClient = app()->make(AdminClient::class);

        $ipRanges = collect();
        $currentPage = 0;
        do {
            $currentPage++;
            $page = $networkingAdminClient->ipRanges()->getPage($currentPage, 15, [
                'auto_deploy_environment:eq' => 'ecloud nsx',
                'auto_deploy_datacentre_id:eq' => $availabilityZone->datacentre_site_id,
                'type:eq' => 'External'
            ]);
            $ipRanges = $ipRanges->merge($page->getItems());
        } while ($currentPage < $page->totalPages());

        // As the ip ranges are loaded from all az's for the region we need to load all fips associated with the region
        $floatingIps = FloatingIp::withRegion($availabilityZone->region->getKey())->whereNotNull('ip_address');

        $runningTotal = [
            'total' => 0,
            'used' => 0
        ];

        foreach ($ipRanges as $ipRange) {
            $subnet = Subnet::fromString(long2ip($ipRange->networkAddress) . '/' . $ipRange->cidr);
            if (empty($subnet)) {
                throw new \Exception('Failed to load subnet details from IP range ' . $ipRange->id);
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

            $this->data['ip-ranges'][$ipRange->id]['total_ip_count'] = $rangeTotal;
            $this->data['ip-ranges'][$ipRange->id]['available_ip_count'] = $rangeAvailable;
        }

        $percentRemaining = round((($runningTotal['total'] - $runningTotal['used'])/$runningTotal['total']) * 100, 2);

        $this->data['running_totals'] = $runningTotal;
        $this->data['running_totals']['percent_remaining'] = $percentRemaining;

        if ($availabilityZoneCapacity->current != $percentRemaining) {
            $availabilityZoneCapacity->current = $percentRemaining;
            $availabilityZoneCapacity->save();
        }

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
