<?php

namespace App\Listeners\V2\Volume;

use App\Events\V2\Task\Updated;
use App\Listeners\V2\Billable;
use App\Models\V2\BillingMetric;
use App\Models\V2\Task;
use App\Models\V2\Volume;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateBilling implements Billable
{
    /**
     * @param Updated $event
     * @return void
     * @throws \Exception
     */
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        if ($event->model instanceof Volume) {
            $volume = $event->model;
        }

        if ($event->model instanceof Task) {
            if ($event->model->name !== Sync::TASK_NAME_UPDATE) {
                return;
            }

            if (!$event->model->completed) {
                return;
            }

            if (get_class($event->model->resource) != Volume::class) {
                return;
            }

            $volume = $event->model->resource;
        }

        if (empty($volume)) {
            return;
        }

        if ($volume->instances()->whereNotNull('load_balancer_id')->count() > 0) {
            return;
        }

        // If iops is empty, get the default value
        if (empty($volume->iops)) {
            $volume->iops = config('volume.iops.default', 300);
        }

        $time = Carbon::now();

        $currentActiveMetric = BillingMetric::getActiveByKey($volume, self::getKeyName('%'), 'LIKE');

        if (!empty($currentActiveMetric)) {
            if (($currentActiveMetric->value == $volume->capacity) &&
                ($currentActiveMetric->key == self::getKeyName($volume->iops))) {
                return;
            }
            $currentActiveMetric->end = $time;
            $currentActiveMetric->save();
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $volume->id;
        $billingMetric->vpc_id = $volume->vpc->id;
        $billingMetric->reseller_id = $volume->vpc->reseller_id;
        $billingMetric->name = self::getFriendlyName($volume->iops);
        $billingMetric->key = self::getKeyName($volume->iops);
        $billingMetric->value = $volume->capacity;
        $billingMetric->start = $time;

        $product = $volume->availabilityZone
            ->products()
            ->where('product_name', 'LIKE', '%volume@'.$volume->iops.'%')
            ->first();
        if (empty($product)) {
            Log::error(
                'Failed to load \'volume\' billing product for availability zone '.
                $volume->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($volume->vpc->reseller_id);
        }

        $billingMetric->save();

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }

    /**
     * Gets the friendly name for the billing metric
     * @return string
     */
    public static function getFriendlyName(): string
    {
        $argument = (count(func_get_args()) > 0) ? func_get_arg(0) . ' IOPS' : '';
        return sprintf('Volume %s', $argument);
    }

    /**
     * Gets the billing metric key
     * @return string
     */
    public static function getKeyName(): string
    {
        $argument = (count(func_get_args()) > 0) ? '.' . func_get_arg(0) : '';
        return sprintf('disk.capacity%s', $argument);
    }
}
