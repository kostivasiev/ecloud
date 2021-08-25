<?php

namespace App\Listeners\V2\VpnSession;

use App\Models\V2\BillingMetric;
use App\Models\V2\Product;
use App\Models\V2\VpnSession;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateBilling
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        if ($event->model->name !== Sync::TASK_NAME_UPDATE) {
            return;
        }

        if (!$event->model->completed) {
            return;
        }

        if (get_class($event->model->resource) != VpnSession::class) {
            return;
        }

        $vpnSession = $event->model->resource;

        $currentActiveMetric = BillingMetric::where('resource_id', $vpnSession->id)
            ->where('key', '=', 'vpn.site-to-site.session')
            ->whereNull('end')
            ->first();

        if (!empty($currentActiveMetric)) {
            return;
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->fill([
            'resource_id' => $vpnSession->id,
            'vpc_id' => $vpnSession->vpnService->router->vpc->id,
            'reseller_id' => $vpnSession->vpnService->router->vpc->reseller_id,
            'key' => 'vpn.site-to-site.session',
            'value' => 1,
            'start' => Carbon::now(),
        ]);

        $productName = $vpnSession->vpnService->availabilityZone->id . ': site to site vpn';
        /** @var Product $product */
        $product = $vpnSession->vpnService->availabilityZone
            ->products()
            ->where('product_name', $productName)
            ->first();
        if (empty($product)) {
            Log::error(
                'Failed to load "' . $productName . '" billing product for availability zone ' . $vpnSession->vpnService->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($vpnSession->vpnService->router->vpc->reseller_id);
        }

        $billingMetric->save();

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}
