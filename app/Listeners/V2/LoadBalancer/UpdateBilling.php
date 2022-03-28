<?php

namespace App\Listeners\V2\LoadBalancer;

use App\Events\V2\Task\Updated;
use App\Listeners\V2\Billable;
use App\Models\V2\BillingMetric;
use App\Models\V2\LoadBalancer;
use App\Models\V2\LoadBalancerSpecification;
use App\Models\V2\Product;
use App\Support\Sync;
use App\Traits\V2\Listeners\BillableListener;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UpdateBilling implements Billable
{
    use BillableListener;

    const RESOURCE = LoadBalancer::class;

    public function handle(Updated $event)
    {
        if (!$this->validateBillableResourceEvent($event)) {
            return;
        }
        $loadBalancer = $event->model->resource;

        if (get_class($loadBalancer) != LoadBalancer::class) {
            return;
        }

        $time = Carbon::now();

        $currentActiveMetric = BillingMetric::where('resource_id', $loadBalancer->id)
            ->where('key', 'like', self::getKeyName('%'))
            ->whereNull('end')
            ->first();

        if (!empty($currentActiveMetric)) {
            if ($currentActiveMetric->key == self::getKeyName($loadBalancer->loadBalancerSpec->name)) {
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
            'name' => self::getFriendlyName($loadBalancer->loadBalancerSpec->name),
            'key' => self::getKeyName($loadBalancer->loadBalancerSpec->name),
            'value' => 1,
            'start' => $time,
        ]);

        $productName = $loadBalancer->availabilityZone->id . ': ' . self::getFriendlyName($loadBalancer->loadBalancerSpec->name);

        /** @var Product $product */
        $product = $loadBalancer->availabilityZone
            ->products()
            ->where('product_name', $productName)
            ->first();
        if (empty($product)) {
            Log::error(
                'Failed to load "' . $productName . '" billing product for availability zone '.
                $loadBalancer->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($loadBalancer->vpc->reseller_id);
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
        $argument = (count(func_get_args()) > 0) ? func_get_arg(0) : '';
        $lbSpec = LoadBalancerSpecification::withTrashed()->find($argument);
        if ($lbSpec) {
            $argument = $lbSpec->name;
        }
        return sprintf('%s Load Balancer', ucwords($argument));
    }

    /**
     * Gets the billing metric key
     * @return string
     */
    public static function getKeyName(): string
    {
        $argument = (count(func_get_args()) > 0) ? Str::lower(func_get_arg(0)) : '';
        return sprintf('load-balancer.%s', $argument);
    }
}
