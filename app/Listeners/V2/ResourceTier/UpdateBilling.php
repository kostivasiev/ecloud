<?php

namespace App\Listeners\V2\ResourceTier;

use App\Listeners\V2\Billable;
use App\Models\V2\BillingMetric;
use App\Models\V2\Product;
use App\Models\V2\ResourceTier;
use App\Traits\V2\Listeners\BillableListener;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateBilling implements Billable
{
    use BillableListener;

    const RESOURCE = ResourceTier::class;

    public function handle($event)
    {
        if (!$this->validateBillableResourceEvent($event)) {
            return;
        }
        $model = $event->model->resource;
        $hostGroup = $model->hostGroups->first();
        $hostSpec = $hostGroup->hostSpec;

        if ($hostSpec->id !== 'hs-high-cpu') {
            return;
        }

        $time = Carbon::now();

        $currentActiveMetric = BillingMetric::where('resource_id', $model->id)
            ->where('key', 'like', self::getKeyName())
            ->whereNull('end')
            ->first();

        if (!empty($currentActiveMetric)) {
            if ($currentActiveMetric->key == self::getKeyName()) {
                return;
            }
            $currentActiveMetric->end = $time;
            $currentActiveMetric->save();
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->fill([
            'resource_id' => $model->id,
            'vpc_id' => $hostGroup->vpc->id,
            'reseller_id' => $hostGroup->vpc->reseller_id,
            'name' => self::getFriendlyName(),
            'key' => self::getKeyName(),
            'value' => 1,
            'start' => $time,
        ]);

        $productName = $model->availabilityZone->id . ': ' . self::getKeyName();
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
        return 'High Cpu';
    }

    /**
     * Gets the billing metric key
     * @return string
     */
    public static function getKeyName(): string
    {
        return 'high.cpu';
    }
}