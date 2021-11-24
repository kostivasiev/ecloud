<?php

namespace App\Console\Commands\Billing;

use App\Models\V2\BillingMetric;
use App\Models\V2\Vpc;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FixPriceOnAdvancedNetworkingBillingMetrics extends Command
{
    protected $signature = 'billing:advanced-networking-fix-price {--T|test-run}';

    protected $description = 'Fix multi-az error in advanced networking price';

    public function handle()
    {
        Vpc::where('advanced_networking', '=', true)->each(function ($vpc) {
            Cache::lock('billing.networking.advanced.'  . $vpc->id, 60)->block(60, function () use ($vpc) {
                $currentActiveMetric = BillingMetric::getActiveByKey($vpc, 'networking.advanced');

                if (!$currentActiveMetric || !empty($currentActiveMetric->price)) {
                    return;
                }

                $availabilityZone = $vpc->region->availabilityZones()->where('is_public', true)->first();

                $product = $availabilityZone->products()->where('product_name', $availabilityZone->id . ': advanced networking')->first();
                if (empty($product)) {
                    Log::error(
                        'Failed to load billing product ' . $availabilityZone->id . ': advanced networking'
                    );
                } else {
                    $currentActiveMetric->category = $product->category;
                    $currentActiveMetric->price = $product->getPrice($vpc->reseller_id);
                }

                $this->info(
                    'Updating metric ' . $currentActiveMetric->id .
                    ', setting category ' . $currentActiveMetric->category .
                    ', setting price ' . $currentActiveMetric->price
                );

                if (!$this->option('test-run')) {
                    $currentActiveMetric->save();
                }
            });
        });

        $this->info('Complete!');

        return Command::SUCCESS;
    }
}
