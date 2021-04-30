<?php
namespace App\Listeners\V2\Host;

use App\Events\V2\Task\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\Host;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Class EndHostGroupBilling
 * Toggle billing for a host group when a host is deleted as we want to bill for empty host groups.
 * @package App\Listeners\V2\Host
 */
class ToggleHostGroupBilling
{
    public function handle(Updated $event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        $task = $event->model;

        if (!$task->completed || !($task->resource instanceof Host)) {
            return;
        }

        $host = $task->resource;

        switch ($task->name) {
            case Sync::TASK_NAME_UPDATE:
                $billingMetric = BillingMetric::getActiveByKey($host->hostGroup, 'hostgroup');
                if ($billingMetric) {
                    Log::debug(get_class($this) . ': Billing ended for non empty host group ' . $host->hostGroup->id);
                    $billingMetric->setEndDate();
                }
                break;
            case Sync::TASK_NAME_DELETE:
                // <= 1 because the host hasn't been trashed yet
                if ($host->hostGroup->hosts->count() <= 1 && (!BillingMetric::getActiveByKey($host->hostGroup, 'hostgroup'))) {
                    $billingMetric = app()->make(BillingMetric::class);
                    $billingMetric->fill([
                        'resource_id' => $host->hostGroup->id,
                        'vpc_id' => $host->hostGroup->vpc->id,
                        'reseller_id' => $host->hostGroup->vpc->reseller_id,
                        'key' => 'hostgroup',
                        'value' => 1,
                        'start' => Carbon::now(),
                        'category' => 'Compute',
                    ]);

                    $product = $host->hostGroup->availabilityZone
                        ->products()
                        ->where('product_name', $host->hostGroup->availabilityZone->id . ': hostgroup')
                        ->first();

                    if (empty($product)) {
                        Log::error(
                            'Failed to load \'hostgroup\' billing product for availability zone ' . $host->hostGroup->availabilityZone->id
                        );
                    } else {
                        $billingMetric->category = $product->category;
                        $billingMetric->price = $product->getPrice($host->hostGroup->vpc->reseller_id);
                    }
                    $billingMetric->save();
                    Log::debug(get_class($this) . ': Billing started for empty host group ' . $host->hostGroup->id);
                }
                break;
            default:
                Log::error(get_class($this) . ': Unrecognised sync type ' . $task->name);
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}
