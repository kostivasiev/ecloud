<?php
namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Models\V2\Vpc;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use IPLib\Factory;

class CreateManagementNetwork extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;

    private Task $task;
    private Router $model;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        $router = $this->model;
        if (!empty($this->task->data['management_router_id'])) {
            // need to check that the router is up and running
            $managementRouter = Router::find($this->task->data['management_router_id']);
            if ($managementRouter) {
                $this->awaitSyncableResources([
                    $managementRouter->id,
                ]);
            }
            if (empty($this->task->data['management_network_id'])) {
                Log::info(get_class($this) . ' - Create Management Network Start', ['router_id' => $managementRouter->id]);

                $managementNetwork = app()->make(Network::class);
                $managementNetwork->name = 'Management Network for ' . $managementRouter->id;
                $managementNetwork->router_id = $managementRouter->id; // needs to be the management router

                $subnet = $router->vpc->advanced_networking ?
                    config('network.management_range.advanced') :
                    config('network.management_range.standard');
                $managementNetwork->subnet = $this->getNextAvailableSubnet($subnet, $managementRouter->availability_zone_id);

                $managementNetwork->syncSave();

                // Store the management network id, so we can backoff everything else
                $this->task->data = Arr::add($this->task->data, 'management_network_id', $managementNetwork->id);
                $this->task->saveQuietly();

                Log::info(get_class($this) . ' - Create Management Network End', [
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
        Log::info(get_class($this) . ' - Start Subnet', ['subnet' => $subnet]);
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
        $subnet = $newRange->asSubnet()->toString();

        // Check database
        $networkCollection = Network::whereHas('router.availabilityZone', function ($query) use ($availabilityZoneId) {
            $query->where('availability_zones.id', '=', $availabilityZoneId);
        })->get();

        foreach ($networkCollection as $network) {
            $range = Factory::rangeFromString($network->subnet);
            if ($range->containsRange($newRange)) {
                Log::info(get_class($this) . ' - Subnet in use', ['subnet' => $subnet]);
                return $this->getNextAvailableSubnet($subnet, $availabilityZoneId, false);
            }
        }

        Log::info(get_class($this) . ' - Next Subnet', ['subnet' => $subnet]);
        return $subnet;
    }
}
