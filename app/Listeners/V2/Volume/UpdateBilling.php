<?php

namespace App\Listeners\V2\Volume;

use App\Events\V2\Sync\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\Sync;
use App\Models\V2\Volume;
use App\Support\Resource;
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
            if (!$event->model->completed) {
                return;
            }

            if (Resource::classFromId($event->model->resource_id) != Volume::class) {
                return;
            }

            $volume = Volume::find($event->model->resource_id);
        }

        if (empty($volume)) {
            return;
        }

        $billingIops = $volume->iops;
        // If iops is empty, or if the volume is unmounted, then set the iops to the default for billing purposes
        if (empty($volume->iops) || ($volume->instances()->get()->count() === 0)) {
            $volume->iops = config('volume.iops.default', 300);
            $billingIops = $volume->iops;
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
        $billingMetric->key = 'disk.capacity.'.$billingIops;
        $billingMetric->value = $volume->capacity;
        $billingMetric->start = $time;

        $product = $volume->availabilityZone
            ->products()
            ->where('product_name', 'LIKE', '%volume@'.$billingIops.'%')
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
