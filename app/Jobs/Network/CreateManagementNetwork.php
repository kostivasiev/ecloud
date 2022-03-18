<?php

namespace App\Jobs\Network;

use App\Jobs\TaskJob;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Traits\V2\TaskJobs\AwaitResources;
use Illuminate\Support\Facades\Cache;
use IPLib\Factory;

class CreateManagementNetwork extends TaskJob
{
    use AwaitResources;

    public function handle()
    {
        $vpc = $this->task->resource;

        if (empty($this->task->data['management_router_id'])) {
            $message = 'Unable to determine management router.';
            $this->error($message);
            $this->fail(new \Exception($message));
            return;
        }

        $managementRouter = Router::find($this->task->data['management_router_id']);
        if (empty($managementRouter)) {
            $message = 'Unable to load management router.';
            $this->error($message);
            $this->fail(new \Exception($message));
            return;
        }

        if (empty($this->task->data['management_network_id'])) {
            if ($managementRouter->networks()->count() > 0) {
                $this->info('A management network was detected, skipping.', [
                    'vpc_id' => $vpc->id
                ]);
                $this->task->updateData('management_network_id', $managementRouter->networks()->first()->id);
                return;
            }

            $this->info('Create Management Network Start', ['router_id' => $managementRouter->id]);

            $managementNetwork = app()->make(Network::class);
            $managementNetwork->name = 'Management Network for ' . $managementRouter->id;
            $managementNetwork->router_id = $managementRouter->id; // needs to be the management router

            $subnet = $vpc->advanced_networking ?
                config('network.management_range.advanced') :
                config('network.management_range.standard');

            $lock = Cache::lock('subnet.' . $subnet, 60);
            try {
                $managementNetwork->subnet = $this->getNextAvailableSubnet($subnet,
                    $managementRouter->availability_zone_id);
                $managementNetwork->syncSave();
            } finally {
                $lock->release();
            }

            // Store the management network id, so we can backoff everything else
            $this->task->updateData('management_network_id', $managementNetwork->id);

            $this->info('Create Management Network End', [
                'router_id' => $managementRouter->id,
                'network_id' => $managementNetwork->id,
            ]);
        }

        if ($this->task->data['management_network_id']) {
            $this->awaitSyncableResources([
                $this->task->data['management_network_id']
            ]);
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
