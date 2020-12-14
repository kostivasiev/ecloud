<?php

namespace App\Listeners\V2\FloatingIp;

use App\Events\V2\FloatingIp\Created;
use App\Jobs\AvailabilityZoneCapacity\UpdateFloatingIpCapacity;
use App\Models\V2\FloatingIp;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use IPLib\Range\Subnet;
use UKFast\Admin\Networking\AdminClient;

class AllocateIp implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param Created $event
     * @return void
     * @throws Exception
     */
    public function handle(Created $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $floatingIp = FloatingIp::find($event->model->getKey());
        if (empty($floatingIp)) {
            $error = 'Failed to allocate floating IP to ' . $event->model->getKey() . '. Resource has been deleted';
            Log::error($error);
            $this->fail(new \Exception($error));
            return;
        }
        $logMessage = 'Allocate external Ip to floating IP ' . $floatingIp->getKey() . ': ';

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
                    $floatingIp->save();
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
                        'availability_zone_id' => $availabilityZone->getKey()
                    ]));
                });

                return;
            }
        }

        $error = 'Insufficient available external IP\'s to assign to floating IP resource ' . $floatingIp->getKey();
        Log::error($error);
        $this->fail(new \Exception($error));
        return;

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
