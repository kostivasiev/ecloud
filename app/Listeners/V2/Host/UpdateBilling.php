<?php
namespace App\Listeners\V2\Host;

use App\Events\V2\Sync\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\Host;
use App\Models\V2\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Class UpdateBilling
 * Add a billing metric when a dedicated host is created
 * @package App\Listeners\V2\Host
 */
class UpdateBilling
{
    public function handle(Updated $event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        $sync = $event->model;

        if (!$sync->completed
            || $sync->type != Sync::TYPE_UPDATE
            || !($sync->resource instanceof Host)
        ) {
            return;
        }

        $host = $sync->resource;

        $currentActiveMetric = BillingMetric::getActiveByKey($host, $host->hostGroup->hostSpec->id);
        if (!empty($currentActiveMetric)) {
            return;
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->fill([
            'resource_id' => $host->id,
            'vpc_id' => $host->hostGroup->vpc->id,
            'reseller_id' => $host->hostGroup->vpc->reseller_id,
            'key' => 'host.' . $host->hostGroup->hostSpec->id,
            'value' => 1,
            'start' => Carbon::now(),
            'category' => 'Compute',
        ]);

        $product = $host->hostGroup->availabilityZone
            ->products()
            ->where('product_name', 'LIKE', '%' . $host->hostGroup->hostSpec->id)
            ->first();
        if (empty($product)) {
            Log::error(
                get_class($this) . ': Failed to load \'host spec\' billing product \'' . $host->hostGroup->hostSpec->id
                . '\' for availability zone ' . $host->hostGroup->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($host->hostGroup->vpc->reseller_id);
        }

        $billingMetric->save();

        Log::debug(get_class($this) . ': Added billing metric for ' . $host->id);

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}
