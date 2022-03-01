<?php

namespace App\Listeners\V2\Host;

use App\Events\V2\Task\Updated;
use App\Listeners\V2\Billable;
use App\Models\V2\BillingMetric;
use App\Models\V2\Host;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateLicenseBilling implements Billable
{
    /**
     * @param Updated $event
     * @return void
     * @throws \Exception
     */
    public function handle(Updated $event)
    {
        $task = $event->model;

        if ($event->model->name !== Sync::TASK_NAME_UPDATE) {
            return;
        }

        if (!$event->model->completed) {
            return;
        }

        if (!($task->resource instanceof Host)) {
            return;
        }

        $host = $task->resource;

        if (!$host->hostGroup->windows_enabled) {
            return;
        }

        $currentActiveMetric = BillingMetric::getActiveByKey($host, self::getKeyName());
        if (!empty($currentActiveMetric)) {
            return;
        }

        $hostSpec = $host->hostGroup->hostSpec;

        // We need to charge by the total number of cores, with a minimum of 16 cores
        // cpu_sockets is the number of actual CPU's and cpu_cores is the number of cores each CPU has.
        $cores = $hostSpec->cpu_sockets * $hostSpec->cpu_cores;
        if ($cores < config('host.billing.windows.min_cores')) {
            Log::debug('Number of cores for host spec ' . $hostSpec->id . ' (' . $cores . ') is less than billing minimum of '
                . config('host.billing.windows.min_cores') . '. Inserting billing for minimum value instead.');
            $cores = config('host.billing.windows.min_cores');
        }

        // Divide by 2 because billing product is for Win license 2 core pack.
        $cores = ceil($cores/2);

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $host->id;
        $billingMetric->vpc_id = $host->hostGroup->vpc->id;
        $billingMetric->reseller_id = $host->hostGroup->vpc->reseller_id;
        $billingMetric->name = self::getFriendlyName();
        $billingMetric->key = self::getKeyName();
        $billingMetric->value = $cores;
        $billingMetric->start = Carbon::now();

        $product = $host->hostGroup->availabilityZone
            ->products()
            ->where('product_name', $host->hostGroup->availabilityZone->id . ': host windows-os-license')
            ->first();

        if (empty($product)) {
            Log::error(
                'Failed to load \'host windows-os-license\' billing product for availability zone ' . $host->hostGroup->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($host->hostGroup->vpc->reseller_id);
        }

        $billingMetric->save();
    }

    /**
     * Gets the friendly name for the billing metric
     * @return string
     */
    public static function getFriendlyName(): string
    {
        return 'Host Windows License';
    }

    /**
     * Gets the billing metric key
     * @return string
     */
    public static function getKeyName(): string
    {
        return 'host.license.windows';
    }
}
