<?php
namespace App\Listeners\V2\Vpc;

use App\Events\V2\Task\Updated;
use App\Listeners\V2\Billable;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UpdateAdvancedNetworkingBilling implements Billable
{
    public function handle(Updated $event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        if (!($event->model instanceof Task) || !($event->model->resource instanceof Instance)) {
            return;
        }
        if (!$event->model->completed) {
            return;
        }

        if ($event->model->resource->isManaged()) {
            return;
        }

        $vpc = $event->model->resource->vpc;

        if (!$vpc->advanced_networking) {
            return;
        }

        Cache::lock('billing.networking.advanced.'  . $vpc->id, 60)->block(60, function () use ($vpc, $event) {
            $value = $vpc->instances->reject(function ($instance) {
                return $instance->isManaged();
            })->sum('ram_capacity');

            if ($event->model->name == Sync::TASK_NAME_DELETE) {
                // The resource isnt actually marked as deleted until the delete batch completes
                $value -= $event->model->resource->ram_capacity;
            }

            $currentActiveMetric = BillingMetric::getActiveByKey($vpc, self::getKeyName());

            if (!empty($currentActiveMetric)) {
                if ($currentActiveMetric->value == $value) {
                    return;
                }

                $currentActiveMetric->setEndDate();
            }

            if ($value == 0) {
                return;
            }

            $billingMetric = app()->make(BillingMetric::class);
            $billingMetric->resource_id = $vpc->id;
            $billingMetric->vpc_id = $vpc->id;
            $billingMetric->reseller_id = $vpc->reseller_id;
            $billingMetric->name = self::getFriendlyName();
            $billingMetric->key = self::getKeyName();
            $billingMetric->value = $value;

            $availabilityZone = $event->model->resource->availabilityZone;
            $product = $availabilityZone->products()
                ->where('product_name', $availabilityZone->id . ': ' . Str::lower(self::getFriendlyName()))
                ->first();
            if (empty($product)) {
                Log::error(
                    'Failed to load billing product ' . $availabilityZone->id . ': '.
                    Str::lower(self::getFriendlyName())
                );
            } else {
                $billingMetric->category = $product->category;
                $billingMetric->price = $product->getPrice($vpc->reseller_id);
            }

            $billingMetric->save();
        });

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }

    /**
     * Gets the friendly name for the billing metric
     * @return string
     */
    public static function getFriendlyName(): string
    {
        return 'Advanced Networking';
    }

    /**
     * Gets the billing metric key
     * @return string
     */
    public static function getKeyName(): string
    {
        return 'networking.advanced';
    }
}
