<?php

namespace App\Listeners\V2\Host;

use App\Events\V2\Sync\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\Host;
use App\Models\V2\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateLicenseBilling
{
    /**
     * @param Updated $event
     * @return void
     * @throws \Exception
     */
    public function handle(Updated $event)
    {
        $sync = $event->model;

        if (!$sync->completed
            || $sync->type != Sync::TYPE_UPDATE
            || !($sync->resource instanceof Host)
        ) {
            return;
        }

        $host = $sync->resource;

        if (!$host->hostGroup->windows_enabled) {
            return;
        }

        $currentActiveMetric = BillingMetric::getActiveByKey($host, 'host.license.windows');
        if (!empty($currentActiveMetric)) {
            return;
        }

        $hostSpec = $host->hostGroup->hostSpec;

        // We need to charge by the total number of cores, with a minimum of 16 cores
        // cpu_sockets is the number of actual CPU's and cpu_cores is the number of cores each CPU has.
        $cores = $hostSpec->cpu_sockets * $hostSpec->cpu_cores;
        if ($cores < config('host.billing.windows.min_cores')) {
            Log::debug('Number of cores for host spec ' . $hostSpec->id . ' (' . $cores . ') is less than billing minimum of '
                . config('host.billing.windows.min_cores') . '. Inserting billing for minimum value instead.'
            );
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $host->id;
        $billingMetric->vpc_id = $host->hostGroup->vpc->id;
        $billingMetric->reseller_id = $host->hostGroup->vpc->reseller_id;
        $billingMetric->key = 'host.license.windows';
        $billingMetric->value = ($cores >= 16) ? $cores : 16;
        $billingMetric->start = Carbon::now();

        $product = $host->hostGroup->availabilityZone
            ->products()
            ->where('product_name',$host->hostGroup->availabilityZone->id . ': host windows-os-license')
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
}
