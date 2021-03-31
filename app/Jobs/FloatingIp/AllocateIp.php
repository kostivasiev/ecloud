<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\AvailabilityZoneCapacity\UpdateFloatingIpCapacity;
use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use IPLib\Range\Subnet;
use UKFast\Admin\Networking\AdminClient;

class AllocateIp extends Job
{
    use Batchable;

    private FloatingIp $model;

    public function __construct(FloatingIp $model)
    {
        $this->model = $model;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        Log::debug(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $floatingIp = $this->model;
        $logMessage = 'Allocate external Ip to floating IP ' . $floatingIp->id . ': ';

        $datacentreSiteIds = $floatingIp->vpc->region->availabilityZones->pluck('datacentre_site_id')->unique();
        $networkingAdminClient = app()->make(AdminClient::class);

        $ipRanges = collect();
        foreach ($datacentreSiteIds as $datacentreSiteId) {
            $currentPage = 0;
            do {
                $currentPage++;
                $page = $networkingAdminClient->ipRanges()->getPage($currentPage, 15, [
                    'auto_deploy_environment:eq' => 'ecloud nsx',
                    'auto_deploy_datacentre_id:eq' => $datacentreSiteId,
                    'type:eq' => 'External'
                ]);
                $ipRanges = $ipRanges->merge($page->getItems());
            } while ($currentPage < $page->totalPages());
        }

        foreach ($ipRanges as $ipRange) {
            $subnet = Subnet::fromString(long2ip($ipRange->networkAddress) . '/' . $ipRange->cidr);
            if (empty($subnet)) {
                Log::error($logMessage . 'Failed to load subnet details from IP range ' . $ipRange->id);
                continue;
            }

            $iterator = 0;
            $ip = $subnet->getStartAddress(); //First IP / Network address (is reserved)

            while ($ip = $ip->getNextAddress()) {
                $iterator++;

                if ($ip->toString() === $subnet->getEndAddress()->toString() || !$subnet->contains($ip)) {
                    Log::warning($logMessage . 'Insufficient available IP\'s in range ' . $ipRange->id);
                    continue 2;
                }

                $checkIp = $ip->toString();

                $floatingIp->ip_address = $checkIp;

                try {
                    $floatingIp->saveQuietly();
                } catch (\Exception $exception) {
                    // Ip already assigned
                    if ($exception->getCode() == 23000) {
                        //Log::debug($logMessage . 'IP address ' . $floatingIp->ip_address . ' already in use.');
                        continue;
                    }

                    // Any other error
                    $this->fail(new \Exception(
                        $logMessage . 'Failed: ' . $exception->getMessage()
                    ));
                    return;
                }

                Log::info($logMessage . 'Success. IP ' . $floatingIp->ip_address . ' was assigned.');

                $floatingIp->vpc->region->availabilityZones->each(function ($availabilityZone) {
                    dispatch(new UpdateFloatingIpCapacity([
                        'availability_zone_id' => $availabilityZone->id
                    ]));
                });

                return;
            }
        }

        if (empty($floatingIp->ip_address)) {
            $this->release(5);
        }

        Log::debug(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
