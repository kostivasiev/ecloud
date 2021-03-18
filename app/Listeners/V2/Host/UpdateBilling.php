<?php
namespace App\Listeners\V2\Host;

use App\Jobs\HostGroup\UnusedBilling;
use App\Models\V2\BillingMetric;
use App\Models\V2\Host;
use App\Models\V2\Sync;
use App\Support\Resource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateBilling
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        if ($event->model instanceof Host) {
            $model = $event->model;
        }

        if ($event->model instanceof Sync) {
            if (!$event->model->completed) {
                return;
            }

            if (Resource::classFromId($event->model->resource_id) != Host::class) {
                return;
            }

            $model = Host::find($event->model->resource_id);
        }

        if (empty($model)) {
            return;
        }

        // calculate metric here
        $spec = $model->hostGroup->hostSpec;
        $availabilityZoneId = $model->hostGroup->availabilityZone->id;
        $metric = $availabilityZoneId . ': host-' . $spec->cpu_cores . '-' . $spec->cpu_clock_speed.'-'.$spec->ram_capacity;

        $time = Carbon::now();
        $currentActiveMetric = BillingMetric::getActiveByKey($model, $metric . '%', 'LIKE');
        if (!empty($currentActiveMetric)) {
            if ($currentActiveMetric->key == $metric) {
                return;
            }
            $currentActiveMetric->end = $time;
            $currentActiveMetric->save();
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $model->id;
        $billingMetric->vpc_id = $model->hostGroup->vpc->id;
        $billingMetric->reseller_id = $model->hostGroup->vpc->reseller_id;
        $billingMetric->key = $metric;
        $billingMetric->value = $spec->id;
        $billingMetric->start = $time;

        $product = $model->hostGroup->availabilityZone
            ->products()
            ->where('product_name', 'LIKE', '%'.$metric)
            ->first();
        if (empty($product)) {
            Log::error(
                'Failed to load \'host\' billing product for availability zone ' . $this->model->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($model->hostGroup->vpc->reseller_id);
        }

        $billingMetric->save();
        $model->setSyncCompleted();

        dispatch(new UnusedBilling($model->hostGroup));

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}