<?php

namespace App\Listeners\V2\Volume;

use App\Events\V2\Sync\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\Sync;
use App\Models\V2\Volume;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateBilling
{
    /**
     * @param Updated $event
     * @return void
     * @throws \Exception
     */
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['model' => $event->model]);

        if ($event->model instanceof Volume) {
            $volume = $event->model;
        }

        if ($event->model instanceof Sync) {
            if ($event->model->type !== Sync::TYPE_UPDATE) {
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

        // If iops is empty, get the default value
        if (empty($volume->iops)) {
            $volume->iops = config('volume.iops.default', 300);
        }

        $time = Carbon::now();

        $currentActiveMetric = BillingMetric::getActiveByKey($volume, 'disk.capacity.%', 'LIKE');

        if (!empty($currentActiveMetric)) {
            if (($currentActiveMetric->value == $volume->capacity) &&
                ($currentActiveMetric->key == 'disk.capacity.'.$volume->iops)) {
                return;
            }
            $currentActiveMetric->end = $time;
            $currentActiveMetric->save();
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $volume->id;
        $billingMetric->vpc_id = $volume->vpc->id;
        $billingMetric->reseller_id = $volume->vpc->reseller_id;
        $billingMetric->key = 'disk.capacity.'.$volume->iops;
        $billingMetric->value = $volume->capacity;
        $billingMetric->start = $time;

        $product = $volume->availabilityZone
            ->products()
            ->where('product_name', 'LIKE', '%volume@'.$volume->iops.'%')
            ->first();
        if (empty($product)) {
            Log::error(
                'Failed to load \'volume\' billing product for availability zone ' . $volume->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($volume->vpc->reseller_id);
        }

        $billingMetric->save();

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}
