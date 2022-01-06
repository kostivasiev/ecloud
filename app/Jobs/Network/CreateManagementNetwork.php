<?php
namespace App\Jobs\Network;

use App\Jobs\TaskJob;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Traits\V2\TaskJobs\AwaitResources;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use IPLib\Factory;

class CreateManagementNetwork extends TaskJob
{
    use AwaitResources;

    public $lock;

    public function handle()
    {
        $router = $this->task->resource;
        if (!empty($this->task->data['management_router_id'])) {
            $managementRouter = Router::find($this->task->data['management_router_id']);
            if (empty($this->task->data['management_network_id'])) {
                $this->info('Create Management Network Start', ['router_id' => $managementRouter->id]);

                $managementNetwork = app()->make(Network::class);
                $managementNetwork->name = 'Management Network for ' . $managementRouter->id;
                $managementNetwork->router_id = $managementRouter->id; // needs to be the management router

                $subnet = $router->vpc->advanced_networking ?
                    config('network.management_range.advanced') :
                    config('network.management_range.standard');

                $lock = Cache::lock('subnet.'.$subnet, 60);
                try {
                    $managementNetwork->subnet = $this->getNextAvailableSubnet($subnet, $managementRouter->availability_zone_id);
                    $managementNetwork->syncSave();
                } finally {
                    $lock->release();
                }

                // Store the management network id, so we can backoff everything else
                $this->task->data = Arr::add($this->task->data, 'management_network_id', $managementNetwork->id);
                $this->task->saveQuietly();

                $this->info('Create Management Network End', [
                    'router_id' => $managementRouter->id,
                    'network_id' => $managementNetwork->id,
                ]);
            } else {
                $managementNetwork = Network::findOrFail($this->task->data['management_network_id']);
            }

            if ($managementNetwork) {
                $this->awaitSyncableResources([
                    $managementNetwork->id,
                ]);
            }
        }
    }

    public function getNextAvailableSubnet($subnet, $availabilityZoneId, $firstRun = true)
    {
        $this->info('Start Subnet', ['subnet' => $subnet]);
        $range = Factory::rangeFromString($subnet);
        if ($firstRun) {
            $newFromInteger = ip2long($range->getStartAddress()) + 1024;
        } else {
            $newFromInteger = ip2long($range->getEndAddress()) + 1;
        }
        $newFrom = Factory::addressFromString(long2ip($newFromInteger));
        $newToInteger = $newFromInteger + 15;
        $newTo = Factory::addressFromString(long2ip($newToInteger));
        $newRange = Factory::rangeFromBoundaries($newFrom, $newTo);
        $subnet = $newRange->asSubnet()->toString();// Check database
        $networkCollection = Network::whereHas(
            'router.availabilityZone',
            function ($query) use ($availabilityZoneId) {
                $query->where('availability_zones.id', '=', $availabilityZoneId);
                $query->where('routers.is_management', '=', true);
            }
        )->get();

        foreach ($networkCollection as $network) {
            $range = Factory::rangeFromString($network->subnet);
            if ($range->containsRange($newRange)) {
                $this->debug('Subnet in use', ['subnet' => $subnet]);
                return $this->getNextAvailableSubnet($subnet, $availabilityZoneId, false);
            }
        }
        $this->info('Next Subnet', ['subnet' => $subnet]);
        return $subnet;
    }
}
