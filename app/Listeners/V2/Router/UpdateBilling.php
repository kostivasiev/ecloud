<?php

namespace App\Listeners\V2\Router;

use App\Models\V2\BillingMetric;
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

        $model = $event->model;
        if ($model instanceof Task) {
            if ($event->model->name !== Sync::TASK_NAME_UPDATE) {
                return;
            }

            if (!$model->completed) {
                return;
            }

            if (get_class($event->model->resource) != Router::class) {
                return;
            }

            $model = $event->model->resource;
        }

        if (!($model instanceof Router)) {
            return;
        }
        /** @var Router $model */

        // If is management router, don't add a billing entry
        if ($model->is_management) {
            return;
        }

        $time = Carbon::now();

        $currentActiveMetric = BillingMetric::where('resource_id', $model->id)
            ->where('key', 'like', 'throughput.%')
            ->whereNull('end')
            ->first();

        if (!empty($currentActiveMetric)) {
            if ($currentActiveMetric->key == 'throughput.' . $model->routerThroughput->name) {
                return;
            }
            $currentActiveMetric->end = $time;
            $currentActiveMetric->save();
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->fill([
            'resource_id' => $model->id,
            'vpc_id' => $model->vpc->id,
            'reseller_id' => $model->vpc->reseller_id,
            'key' => 'throughput.' . $model->routerThroughput->name,
            'value' => 1,
            'start' => $time,
        ]);

        $productName = $model->availabilityZone->id . ': throughput ' . $model->routerThroughput->name;
        /** @var Product $product */
        $product = $model->availabilityZone
            ->products()
            ->where('product_name', $productName)
            ->first();
        if (empty($product)) {
            Log::error(
                'Failed to load "' . $productName . '" billing product for availability zone ' . $model->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($model->vpc->reseller_id);
        }

        $billingMetric->save();

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}
