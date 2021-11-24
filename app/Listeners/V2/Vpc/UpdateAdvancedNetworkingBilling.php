<?php
namespace App\Listeners\V2\Vpc;

use App\Events\V2\Task\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UpdateAdvancedNetworkingBilling
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
            $time = Carbon::now();

            $value = $vpc->instances->reject(function ($instance) {
                return $instance->isManaged();
            })->sum('ram_capacity');

            if ($event->model->name == Sync::TASK_NAME_DELETE) {
                // The resource isnt actually marked as deleted until the delete batch completes
                $value -= $event->model->resource->ram_capacity;
            }

            $currentActiveMetric = BillingMetric::getActiveByKey($vpc, 'networking.advanced');

            if (!empty($currentActiveMetric)) {
                if ($currentActiveMetric->value == $value) {
                    return;
                }

                $currentActiveMetric->setEndDate($time);
            }

            if ($value == 0) {
                return;
            }

            $billingMetric = app()->make(BillingMetric::class);
            $billingMetric->resource_id = $vpc->id;
            $billingMetric->vpc_id = $vpc->id;
            $billingMetric->reseller_id = $vpc->reseller_id;
            $billingMetric->key = 'networking.advanced';
            $billingMetric->value = $value;
            $billingMetric->start = Carbon::now();

            $availabilityZone = $event->model->resource->availabilityZone;
            $product = $availabilityZone->products()->where('product_name', $availabilityZone->id . ': advanced networking')->first();
            if (empty($product)) {
                Log::error(
                    'Failed to load billing product ' . $availabilityZone->id . ': advanced networking'
                );
            } else {
                $billingMetric->category = $product->category;
                $billingMetric->price = $product->getPrice($vpc->reseller_id);
            }

            $billingMetric->save();
        });

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}
