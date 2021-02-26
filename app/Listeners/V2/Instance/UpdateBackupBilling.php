<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Sync\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use App\Support\Resource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateBackupBilling
{
    /**
     * @param Updated $event
     * @return void
     * @throws \Exception
     */
    public function handle(Updated $event)
    {
        if (!$event->model->completed) {
            return;
        }

        if (!in_array(Resource::classFromId($event->model->resource_id), [Instance::class, Volume::class])) {
            return;
        }

        if (Resource::classFromId($event->model->resource_id) == Volume::class) {
            $volume = Volume::withTrashed()->find($event->model->resource_id);
            // TODO: We will need to look at this when we support volumes being attached to multiple instances.
            $instance = $volume->instances()->first();
        } else {
            $instance = Instance::find($event->model->resource_id);
        }

        if (empty($instance)) {
            return;
        }

        $time = Carbon::now();

        $currentActiveMetric = BillingMetric::getActiveByKey($instance, 'backup.quota');

        if (!$instance->backup_enabled && empty($currentActiveMetric)) {
            return;
        }

        if (!$instance->backup_enabled && !empty($currentActiveMetric)) {
            $currentActiveMetric->setEndDate($time);
            Log::info(get_class($this) . ' : Backup was disabled for instance', ['instance' => $instance->id]);
            return;
        }

        if (!empty($currentActiveMetric)) {
            if ($currentActiveMetric->value == $instance->volumeCapacity) {
                return;
            }

            $currentActiveMetric->setEndDate($time);
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $instance->id;
        $billingMetric->vpc_id = $instance->vpc->id;
        $billingMetric->reseller_id = $instance->vpc->reseller_id;
        $billingMetric->key = 'backup.quota';
        $billingMetric->value = $instance->volumeCapacity;
        $billingMetric->start = $time;

        $product = $instance->availabilityZone->products()->get()->firstWhere('name', 'backup');
        if (empty($product)) {
            Log::error(
                'Failed to load \'backup\' billing product for availability zone ' . $instance->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($instance->vpc->reseller_id);
        }

        Log::info(get_class($this) . ' : backup.quota set to ' . $instance->volumeCapacity, ['instance' => $instance->id]);
        $billingMetric->save();
    }
}
