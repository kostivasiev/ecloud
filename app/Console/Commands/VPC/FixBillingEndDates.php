<?php
namespace App\Console\Commands\VPC;

use App\Models\V2\BillingMetric;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandResponse;

class FixBillingEndDates extends Command
{
    protected $signature = 'vpc:fix-end-dates {--T|test-run}';
    protected $description = 'Converts billing entries to fix end date issue';

    public function handle()
    {
        $this->info('Fix billing entries' . PHP_EOL);

        BillingMetric::whereIn('key', ['ram.capacity', 'ram.capacity.high', 'vcpu.count'])
            ->each(function ($billingMetric) {
                if ($billingMetric->instance) {
                    $instanceMetric = $billingMetric->instance->billingMetrics()
                        ->where('key', '=', $billingMetric->key)
                        ->whereDate('start', '>', Carbon::createFromDate($billingMetric->start))
                        ->orderByDesc('created_at')
                        ->first();
                    if ($instanceMetric) {
                        $this->info('Modifying: ' . $billingMetric->id);
                        $billingMetric->setAttribute('end', Carbon::createFromDate($instanceMetric->created_at))
                            ->saveQuietly();
                    }
                }
            });


        return CommandResponse::SUCCESS;
    }
}