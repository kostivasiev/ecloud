<?php

namespace App\Listeners\V2\Image;

use App\Models\V2\BillingMetric;
use App\Models\V2\Image;
use App\Models\V2\Product;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateBilling
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        /** @var Router $model */
        $model = $event->model;
        if ($model instanceof Task) {
            if ($event->model->name !== Sync::TASK_NAME_UPDATE) {
                return;
            }

            if (!$model->completed) {
                return;
            }

            if (get_class($event->model->resource) != Image::class) {
                return;
            }

            $model = $event->model->resource;
        }

        if (!($model instanceof Image)) {
            return;
        }

        if ($model->instances()->count() <= 0) {
            return;
        }

        $time = Carbon::now();

        $availabilityZone = $model->instances()->first()->availabilityZone;
        $vpc = $model->instances()->first()->vpc;
        $volumeCapacity = $model->instances()->first()->volume_capacity;

        $currentActiveMetric = BillingMetric::where('resource_id', $model->id)
            ->where('key', '=', 'private.image')
            ->whereNull('end')
            ->first();

        if (!empty($currentActiveMetric)) {
            $currentActiveMetric->end = $time;
            $currentActiveMetric->save();
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->fill([
            'resource_id' => $model->id,
            'vpc_id' => $vpc->id,
            'reseller_id' => $vpc->reseller_id,
            'key' => 'private.image',
            'value' => $volumeCapacity,
            'start' => $time,
        ]);

        $productName = $availabilityZone->id . ': volume-1gb';
        /** @var Product $product */
        $product = $availabilityZone->products()
            ->where('product_name', 'LIKE', '%volume-1gb%')
            ->first();
        if (empty($product)) {
            Log::error(
                'Failed to load "' . $productName . '" billing product for availability zone ' . $availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($vpc->reseller_id);
        }

        $billingMetric->save();

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}
