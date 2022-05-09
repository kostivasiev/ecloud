<?php
namespace App\Console\Commands\VPC;

use App\Models\V2\BillingMetric;
use Carbon\Carbon;
use App\Console\Commands\Command;
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
        $lastMetric = null;

        BillingMetric::whereIn('key', ['ram.capacity', 'ram.capacity.high', 'vcpu.count'])
            ->where('resource_id', 'LIKE', 'i-%')
            ->orderBy('created_at', 'asc')
            ->each(function ($billingMetric) use (&$resellerCount, &$instanceCount, &$metricCount, &$lastReseller, &$lastInstance) {
                if ($billingMetric->instance) {
                    $billingMetric->instance->billingMetrics()
                        ->where('key', '=', $billingMetric->key)
                        ->whereDate('start', '>', Carbon::createFromDate($billingMetric->start))
                        ->orderBy('created_at', 'asc')
                        ->each(function ($instanceMetric) use (&$billingMetric, &$lastReseller, &$resellerCount, &$lastInstance, &$instanceCount, &$lastMetric, &$metricCount) {
                            if ($instanceMetric->end === null) {
                                return;
                            }
                            if ($billingMetric->id !== $lastMetric) {
                                $lastMetric = $billingMetric->id;
                                if ($instanceMetric->reseller_id !== $lastReseller) {
                                    $lastReseller = $instanceMetric->reseller_id;
                                    $resellerCount++;
                                }
                                if ($instanceMetric->instance->id !== $lastInstance) {
                                    $lastInstance = $instanceMetric->instance->id;
                                    $instanceCount++;
                                }
                                $this->info('Modifying: ' . $billingMetric->id);
                                if (!$this->option('test-run')) {
                                    $billingMetric->setAttribute('end', Carbon::createFromDate($instanceMetric->created_at))
                                        ->saveQuietly();
                                }
                                $metricCount++;
                            }
                        });
                }
            });

        $this->info('Affected Resellers: ' . $resellerCount);
        $this->info('Affected Instances: ' . $instanceCount);
        $metricString = 'Metrics Modified: ';
        if ($this->option('test-run')) {
            $metricString = 'Metrics that would be modified: ';
        }
        $this->info($metricString . $metricCount);

        return CommandResponse::SUCCESS;
    }
}
