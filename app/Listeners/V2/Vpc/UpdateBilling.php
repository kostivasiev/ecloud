<?php
namespace App\Listeners\V2\Vpc;

use App\Events\V2\Task\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use App\Models\V2\Task;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateBilling
{
    public function handle(Updated $event)
    {
        Log::info(get_class($this) . ' : Started', ['model' => $event->model]);
        if (!($event->model instanceof Task)) {
            return;
        }
        if (!$event->model->completed) {
            return;
        }

        if (!in_array(get_class($event->model->resource), [Vpc::class, Instance::class])) {
            return;
        }

        $vpc = ($event->model->resource instanceof Vpc) ? $event->model->resource : $event->model->resource->vpc;

        $time = Carbon::now();

        $value = $vpc->instances->sum('ram_capacity');

        if ($event->model->name == Sync::TASK_NAME_DELETE && $event->model->resource instanceof Instance) {
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

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $vpc->id;
        $billingMetric->vpc_id = $vpc->id;
        $billingMetric->reseller_id = $vpc->reseller_id;
        $billingMetric->key = 'networking.advanced';
        $billingMetric->value = $value;
        $billingMetric->start = Carbon::now();

        $availabilityZone = $vpc->region->availabilityZones()->first();
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

        Log::info(get_class($this) . ' : Finished', ['model' => $event->model]);
    }
}
