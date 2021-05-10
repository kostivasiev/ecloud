<?php

namespace App\Listeners\V2\HostGroup;

use App\Events\V2\Task\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\HostGroup;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Class UpdateBilling
 * Add host group billing when a host group is created
 * @package App\Listeners\V2\HostGroup
 */
class UpdateBilling
{
    public function handle(Updated $event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        $task = $event->model;

        if ($event->model->name !== Sync::TASK_NAME_UPDATE) {
            return;
        }

        if (!$event->model->completed) {
            return;
        }

        if (!($task->resource instanceof HostGroup)) {
            return;
        }

        $hostGroup = $task->resource;

        if (!BillingMetric::getActiveByKey($hostGroup, 'hostgroup')) {
            $billingMetric = app()->make(BillingMetric::class);
            $billingMetric->fill([
                'resource_id' => $hostGroup->id,
                'vpc_id' => $hostGroup->vpc->id,
                'reseller_id' => $hostGroup->vpc->reseller_id,
                'key' => 'hostgroup',
                'value' => 1,
                'start' => Carbon::now(),
                'category' => 'Compute',
            ]);

            $product = $hostGroup->availabilityZone
                ->products()
                ->where('product_name', $hostGroup->availabilityZone->id . ': hostgroup')
                ->first();

            if (empty($product)) {
                Log::error(
                    'Failed to load \'hostgroup\' billing product for availability zone ' . $hostGroup->availabilityZone->id
                );
            } else {
                $billingMetric->category = $product->category;
                $billingMetric->price = $product->getPrice($hostGroup->vpc->reseller_id);
            }

            $billingMetric->save();
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}
