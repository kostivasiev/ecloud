<?php

namespace App\Listeners\V2\LoadBalancer;

use App\Events\V2\Task\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\LoadBalancer;
use App\Models\V2\Product;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateBilling
{
    public function handle(Updated $event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        if ($event->model->name !== Sync::TASK_NAME_UPDATE) {
            return;
        }

        if (!$event->model->completed) {
            return;
        }

        if (get_class($event->model->resource) != LoadBalancer::class) {
            return;
        }

        $loadBalancer = $event->model->resource;

        if (get_class($loadBalancer) != LoadBalancer::class) {
            return;
        }

        $time = Carbon::now();

        $currentActiveMetric = BillingMetric::where('resource_id', $loadBalancer->id)
            ->where('key', 'like', 'load-balancer.%')
            ->whereNull('end')
            ->first();

        if (!empty($currentActiveMetric)) {
            if ($currentActiveMetric->key == 'load-balancer.' . $loadBalancer->loadBalancerSpec->name) {
                return;
            }
            $currentActiveMetric->end = $time;
            $currentActiveMetric->save();
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->fill([
            'resource_id' => $loadBalancer->id,
            'vpc_id' => $loadBalancer->vpc->id,
            'reseller_id' => $loadBalancer->vpc->reseller_id,
            'key' => 'load-balancer.' . $loadBalancer->loadBalancerSpec->name,
            'value' => 1,
            'start' => $time,
        ]);

        $productName = $loadBalancer->availabilityZone->id . ': load balancer ' . $loadBalancer->loadBalancerSpec->name;

        /** @var Product $product */
        $product = $loadBalancer->availabilityZone
            ->products()
            ->where('product_name', $productName)
            ->first();
        if (empty($product)) {
            Log::error(
                'Failed to load "' . $productName . '" billing product for availability zone ' . $loadBalancer->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($loadBalancer->vpc->reseller_id);
        }

        $billingMetric->save();

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}
