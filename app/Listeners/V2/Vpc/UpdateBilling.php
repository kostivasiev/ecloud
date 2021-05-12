<?php
namespace App\Listeners\V2\Vpc;

use App\Events\V2\Task\Updated;
use App\Models\V2\BillingMetric;
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

        if ($event->model->name != Sync::TASK_NAME_UPDATE) {
            return;
        }

        $vpc = $event->model->resource;

        if (get_class($vpc) != Vpc::class) {
            return;
        }

        $currentActiveMetric = BillingMetric::getActiveByKey($vpc, 'networking.advanced');

        if (!empty($currentActiveMetric)) {
            return;
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $vpc->id;
        $billingMetric->vpc_id = $vpc->id;
        $billingMetric->reseller_id = $vpc->reseller_id;
        $billingMetric->key = 'networking.advanced';
        $billingMetric->value = 1;
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
