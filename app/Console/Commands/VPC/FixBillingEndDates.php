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

        $resellerCount = 0;
        $instanceCount = 0;
        $metricCount = 0;
        $lastReseller = null;
        $lastInstance = null;

        BillingMetric::whereIn('key', ['ram.capacity', 'ram.capacity.high', 'vcpu.count'])
            ->each(function ($billingMetric) use (&$resellerCount, &$instanceCount, &$metricCount, &$lastReseller, &$lastInstance) {
                if ($billingMetric->instance) {
                    $instanceMetric = $billingMetric->instance->billingMetrics()
                        ->where('key', '=', $billingMetric->key)
                        ->whereDate('start', '>', Carbon::createFromDate($billingMetric->start))
                        ->orderByDesc('created_at')
                        ->first();
                    if ($instanceMetric) {
                        if ($billingMetric->reseller_id !== $lastReseller) {
                            $lastReseller = $billingMetric->reseller_id;
                            $resellerCount++;
                        }
                        if ($billingMetric->instance->id !== $lastInstance) {
                            $lastInstance = $billingMetric->instance->id;
                            $instanceCount++;
                        }
                        $this->info('Modifying: ' . $billingMetric->id);
                        if (!$this->option('test-run')) {
                            $billingMetric->setAttribute('end', Carbon::createFromDate($instanceMetric->created_at))
                                ->saveQuietly();
                        }
                        $metricCount++;
                    }
                }
            });

        $this->info('Affected Resellers: ' . $resellerCount);
        $this->info('Affected Instances: ' . $instanceCount);
        $metricString = 'Metrics Modified: ';
        if ($this->option('test-run')) {
            $metricString = 'Metrics the would be modified: ';
        }
        $this->info($metricString . $metricCount);

        return CommandResponse::SUCCESS;
    }
}
