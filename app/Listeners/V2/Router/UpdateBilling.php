<?php

namespace App\Listeners\V2\Router;

use App\Listeners\V2\Billable;
use App\Models\V2\BillingMetric;
use App\Models\V2\Product;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UpdateBilling implements Billable
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

        // If is management router, don't add a billing entry
        if ($model->isManaged()) {
            return;
        }

        $time = Carbon::now();

        $currentActiveMetric = BillingMetric::where('resource_id', $model->id)
            ->where('key', 'like', self::getKeyName('%'))
            ->whereNull('end')
            ->first();

        if (!empty($currentActiveMetric)) {
            if ($currentActiveMetric->key == self::getKeyName($model->routerThroughput->name)) {
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
            'friendly_name' => self::getFriendlyName($model->routerThroughput->name),
            'key' => self::getKeyName($model->routerThroughput->name),
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
                'Failed to load "' . $productName . '" billing product for availability zone '.
                $model->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($model->vpc->reseller_id);
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
        $argument = (count(func_get_args()) > 0) ? ' - ' . func_get_arg(0) : '';
        $argument = Str::replace('gb', 'Gb', Str::replace('mb', 'Mb', $argument));
        return sprintf('Router Throughput%s', $argument);
    }

    /**
     * Gets the billing metric key
     * @return string
     */
    public static function getKeyName(): string
    {
        $argument = (count(func_get_args()) > 0) ? '.'.func_get_arg(0) : '';
        return sprintf('throughput%s', $argument);
    }
}
