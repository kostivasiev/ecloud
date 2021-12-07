<?php

namespace App\Listeners\V2\FloatingIp;

use App\Events\V2\Task\Updated;
use App\Listeners\V2\Billable;
use App\Models\V2\BillingMetric;
use App\Models\V2\FloatingIp;
use App\Models\V2\Task;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UpdateBilling implements Billable
{
    /**
     * @param Updated $event
     * @return void
     * @throws \Exception
     */
    public function handle(Updated $event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        if (!($event->model instanceof Task)) {
            return;
        }

        if (!$event->model->completed) {
            return;
        }

        if ($event->model->name != Sync::TASK_NAME_UPDATE) {
            return;
        }

        $floatingIp = $event->model->resource;

        if (get_class($floatingIp) != FloatingIp::class) {
            return;
        }

        $currentActiveMetric = BillingMetric::getActiveByKey($floatingIp, self::getKeyName());

        if (!empty($currentActiveMetric)) {
            return;
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $floatingIp->id;
        $billingMetric->vpc_id = $floatingIp->vpc->id;
        $billingMetric->reseller_id = $floatingIp->vpc->reseller_id;
        $billingMetric->friendly_name = self::getFriendlyName();
        $billingMetric->key = self::getKeyName();
        $billingMetric->value = 1;
        $billingMetric->start = Carbon::now();

        // Bit of a hack to get the az product, as technically a fip isn't associated with an az
        $product = $floatingIp->availabilityZone
            ->products()
            ->where('product_name', $floatingIp->availabilityZone->id . ': ' . Str::lower(self::getFriendlyName()))
            ->first();
        if (empty($product)) {
            Log::error(
                'Failed to load billing product ' . $floatingIp->availabilityZone->id . ': ' .
                Str::lower(self::getFriendlyName())
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($floatingIp->vpc->reseller_id);
        }

        $billingMetric->save();
    }

    /**
     * Gets the friendly name for the billing metric
     * @return string
     */
    public static function getFriendlyName(): string
    {
        return 'Floating Ip';
    }

    /**
     * Gets the billing metric key
     * @return string
     */
    public static function getKeyName(): string
    {
        return 'floating-ip.count';
    }
}
