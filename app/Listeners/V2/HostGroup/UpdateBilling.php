<?php

namespace App\Listeners\V2\HostGroup;

use App\Models\V2\BillingMetric;
use App\Models\V2\HostGroup;
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

        if (Resource::classFromId($event->model->resource_id) != HostGroup::class) {
            return;
        }

        $hostGroup = HostGroup::find($event->model->resource_id);

        if (empty($hostGroup)) {
            return;
        }

        $currentActiveMetric = BillingMetric::getActiveByKey($hostGroup, 'hostgroup');
        if (!empty($currentActiveMetric)) {
            return;
        }

        if ($hostGroup->hosts()->count() > 0) {
            return;
        }

        $billingMetric = app()->make(BillingMetric::class);

        $billingMetric->fill([
            'resource_id' => $hostGroup->id,
            'vpc_id' => $hostGroup->vpc->id,
            'reseller_id' => $hostGroup->vpc->reseller_id,
            'key' => 'hostgroup',
            'value' => 1,
            'start' => Carbon::now(),
            'category' => 'Compute',
        ]);

        $product = $hostGroup->availabilityZone
            ->products()
            ->where('product_name', $hostGroup->availabilityZone->id . ': hostgroup')
            ->first();

        if (empty($product)) {
            Log::error(
                'Failed to load \'hostgroup\' billing product for availability zone ' . $hostGroup->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($hostGroup->vpc->reseller_id);
        }

        $billingMetric->save();

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}
