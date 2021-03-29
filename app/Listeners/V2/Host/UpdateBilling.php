<?php
namespace App\Listeners\V2\Host;

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

        if (!($event->model instanceof Sync)) {
            return;
        }

        if (!$event->model->completed) {
            return;
        }

        if (Resource::classFromId($event->model->resource_id) != Host::class) {
            return;
        }

        $host = Host::withTrashed()->find($event->model->resource_id);

        if (empty($host) || $host->trashed()) {
            return;
        }

        $currentActiveMetric = BillingMetric::getActiveByKey($host, 'host');
        if (!empty($currentActiveMetric)) {
            return;
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->fill([
            'resource_id' => $host->id,
            'vpc_id' => $host->hostGroup->vpc->id,
            'reseller_id' => $host->hostGroup->vpc->reseller_id,
            'key' => $host->hostGroup->hostSpec->id,
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
                'Failed to load \'host spec\' billing product \'' . $host->hostGroup->hostSpec->id
                . '\' for availability zone ' . $host->hostGroup->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($host->hostGroup->vpc->reseller_id);
        }

        $billingMetric->save();
        Log::debug('Added billing metric for ' . $host->id);

        // End host group billing
        $billingMetric = BillingMetric::getActiveByKey($host->hostGroup, 'hostgroup');
        if ($billingMetric) {
            $billingMetric->setEndDate();
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}
