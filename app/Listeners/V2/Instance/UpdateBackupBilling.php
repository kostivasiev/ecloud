<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Task\Updated;
use App\Listeners\V2\Billable;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateBackupBilling implements Billable
{
    /**
     * @param Updated $event
     * @return void
     * @throws \Exception
     */
    public function handle(Updated $event)
    {
        if ($event->model->name !== Sync::TASK_NAME_UPDATE) {
            return;
        }

        if (!$event->model->completed) {
            return;
        }

        if (!in_array(get_class($event->model->resource), [Instance::class, Volume::class])) {
            return;
        }

        if (get_class($event->model->resource) == Volume::class) {
            // TODO: We will need to look at this when we support volumes being attached to multiple instances.
            $instance = $event->model->resource->instances()->first();
        } else {
            $instance = $event->model->resource;
        }

        if (empty($instance) || $instance->isManaged()) {
            return;
        }

        $time = Carbon::now();

        $currentActiveMetric = BillingMetric::getActiveByKey($instance, self::getKeyName());

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
        $billingMetric->friendly_name = self::getFriendlyName();
        $billingMetric->key = self::getKeyName();
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

    /**
     * Gets the friendly name for the billing metric
     * @return string
     */
    public static function getFriendlyName(): string
    {
        return 'Backup Capacity';
    }

    /**
     * Gets the billing metric key
     * @return string
     */
    public static function getKeyName(): string
    {
        return 'backup.quota';
    }
}
